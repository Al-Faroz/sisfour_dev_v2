package id.sch.mtsn4jombang.sisfour.nativebridge;

import android.Manifest;
import android.content.ContentResolver;
import android.content.ContentValues;
import android.content.Context;
import android.content.pm.PackageManager;
import android.media.MediaScannerConnection;
import android.net.Uri;
import android.os.Build;
import android.os.Environment;
import android.provider.MediaStore;
import android.text.TextUtils;
import android.webkit.CookieManager;
import android.webkit.URLUtil;

import org.apache.cordova.CallbackContext;
import org.apache.cordova.CordovaPlugin;
import org.json.JSONArray;
import org.json.JSONException;
import org.json.JSONObject;

import java.io.BufferedInputStream;
import java.io.BufferedOutputStream;
import java.io.File;
import java.io.FileOutputStream;
import java.io.InputStream;
import java.io.OutputStream;
import java.net.HttpURLConnection;
import java.net.URI;
import java.net.URISyntaxException;
import java.net.URL;
import java.net.URLEncoder;
import java.nio.charset.StandardCharsets;
import java.util.Arrays;
import java.util.HashSet;
import java.util.Iterator;
import java.util.Set;

public class SisFourNative extends CordovaPlugin {
    private static final String ACTION_DOWNLOAD = "download";
    private static final String ACTION_DOWNLOAD_POST = "downloadPost";
    private static final String APP_HOST = "sisfour.mtsn4jombang.sch.id";
    private static final int REQUEST_WRITE_STORAGE = 4101;
    private static final int MAX_POST_FIELDS = 600;
    private static final int MAX_POST_BODY_BYTES = 24 * 1024 * 1024;

    private static final Set<String> POST_DOWNLOAD_PATHS = new HashSet<>(
            Arrays.asList(
                    "/statistik/export/pdf",
                    "/kartu/cetak-massal",
                    "/kartu/export-jpg-zip"
            )
    );

    private JSONObject pendingDownload;
    private CallbackContext pendingDownloadCallback;
    private String pendingAction;

    @Override
    public boolean execute(String action, JSONArray args, CallbackContext callbackContext) throws JSONException {
        if (!ACTION_DOWNLOAD.equals(action) && !ACTION_DOWNLOAD_POST.equals(action)) {
            return false;
        }

        JSONObject options = args.optJSONObject(0);
        if (options == null) {
            callbackContext.error("Payload download tidak valid.");
            return true;
        }

        String url = options.optString("url", "");

        if (ACTION_DOWNLOAD_POST.equals(action)) {
            if (!isAllowedPostUrl(url)) {
                callbackContext.error("Endpoint POST download tidak diizinkan.");
                return true;
            }
        } else if (!isAllowedUrl(url)) {
            callbackContext.error("URL download tidak diizinkan.");
            return true;
        }

        if (requiresLegacyStoragePermission()
                && !cordova.hasPermission(Manifest.permission.WRITE_EXTERNAL_STORAGE)) {
            pendingAction = action;
            pendingDownload = options;
            pendingDownloadCallback = callbackContext;
            cordova.requestPermission(
                    this,
                    REQUEST_WRITE_STORAGE,
                    Manifest.permission.WRITE_EXTERNAL_STORAGE
            );
            return true;
        }

        runDownload(action, options, callbackContext);
        return true;
    }

    @Override
    public void onRequestPermissionsResult(int requestCode, String[] permissions, int[] grantResults)
            throws JSONException {
        if (requestCode != REQUEST_WRITE_STORAGE) {
            return;
        }

        String action = pendingAction;
        JSONObject options = pendingDownload;
        CallbackContext callbackContext = pendingDownloadCallback;

        pendingAction = null;
        pendingDownload = null;
        pendingDownloadCallback = null;

        if (callbackContext == null || options == null || action == null) {
            return;
        }

        boolean granted = grantResults.length > 0
                && grantResults[0] == PackageManager.PERMISSION_GRANTED;

        if (!granted) {
            callbackContext.error("Izin penyimpanan ditolak.");
            return;
        }

        runDownload(action, options, callbackContext);
    }

    private boolean requiresLegacyStoragePermission() {
        return Build.VERSION.SDK_INT <= Build.VERSION_CODES.P;
    }

    private void runDownload(String action, JSONObject options, CallbackContext callbackContext) {
        if (ACTION_DOWNLOAD_POST.equals(action)) {
            downloadPost(options, callbackContext);
            return;
        }

        downloadGet(options, callbackContext);
    }

    private void downloadGet(JSONObject options, CallbackContext callbackContext) {
        cordova.getThreadPool().execute(() -> {
            HttpURLConnection connection = null;

            try {
                String url = options.optString("url", "");
                String userAgent = options.optString("userAgent", "");
                String eventDisposition = options.optString("contentDisposition", "");
                String eventMimeType = normalizeMimeType(options.optString("mimetype", ""));
                String cookie = CookieManager.getInstance().getCookie(url);

                connection = (HttpURLConnection) new URL(url).openConnection();
                connection.setInstanceFollowRedirects(false);
                connection.setRequestMethod("GET");
                connection.setConnectTimeout(20000);
                connection.setReadTimeout(120000);
                connection.setRequestProperty("Accept", "*/*");

                if (!TextUtils.isEmpty(cookie)) {
                    connection.setRequestProperty("Cookie", cookie);
                }

                if (!TextUtils.isEmpty(userAgent)) {
                    connection.setRequestProperty("User-Agent", userAgent);
                }

                int status = connection.getResponseCode();

                if (status < 200 || status >= 300) {
                    if (status >= 300 && status < 400) {
                        callbackContext.error(
                                "Download dialihkan oleh server. Sesi mungkin sudah berakhir."
                        );
                    } else {
                        callbackContext.error(
                                "Server menolak download (HTTP " + status + ")."
                        );
                    }
                    return;
                }

                String responseDisposition =
                        connection.getHeaderField("Content-Disposition");

                String contentDisposition =
                        TextUtils.isEmpty(responseDisposition)
                                ? eventDisposition
                                : responseDisposition;

                String responseMimeType =
                        normalizeMimeType(connection.getContentType());

                String mimeType =
                        TextUtils.isEmpty(responseMimeType)
                                ? eventMimeType
                                : responseMimeType;

                if (
                        TextUtils.isEmpty(contentDisposition)
                        || !contentDisposition.toLowerCase().contains("attachment")
                ) {
                    callbackContext.error(
                            "Server tidak mengembalikan attachment yang valid."
                    );
                    return;
                }

                String fileName = URLUtil.guessFileName(
                        url,
                        contentDisposition,
                        mimeType
                );
                fileName = sanitizeFileName(fileName);

                try (InputStream input =
                             new BufferedInputStream(connection.getInputStream())) {
                    saveToDownloads(
                            input,
                            fileName,
                            mimeType
                    );
                }

                JSONObject result = new JSONObject();
                result.put("fileName", fileName);
                result.put("mimeType", mimeType);
                result.put("mode", "get");
                callbackContext.success(result);
            } catch (Exception error) {
                callbackContext.error(
                        messageFor(error, "Download gagal diproses.")
                );
            } finally {
                if (connection != null) {
                    connection.disconnect();
                }
            }
        });
    }

    private void downloadPost(JSONObject options, CallbackContext callbackContext) {
        cordova.getThreadPool().execute(() -> {
            HttpURLConnection connection = null;

            try {
                String url = options.optString("url", "");
                String userAgent = options.optString("userAgent", "");
                String cookie = CookieManager.getInstance().getCookie(url);
                String body = encodeFields(options.optJSONArray("fields"));

                byte[] bodyBytes = body.getBytes(StandardCharsets.UTF_8);
                if (bodyBytes.length > MAX_POST_BODY_BYTES) {
                    callbackContext.error("Payload export terlalu besar untuk diproses di APK.");
                    return;
                }

                connection = (HttpURLConnection) new URL(url).openConnection();
                connection.setInstanceFollowRedirects(false);
                connection.setRequestMethod("POST");
                connection.setDoOutput(true);
                connection.setConnectTimeout(20000);
                connection.setReadTimeout(120000);
                connection.setRequestProperty(
                        "Content-Type",
                        "application/x-www-form-urlencoded; charset=UTF-8"
                );
                connection.setRequestProperty(
                        "Accept",
                        options.optString("accept", "application/octet-stream")
                );
                connection.setRequestProperty(
                        "X-Requested-With",
                        "XMLHttpRequest"
                );

                if (!TextUtils.isEmpty(cookie)) {
                    connection.setRequestProperty("Cookie", cookie);
                }

                if (!TextUtils.isEmpty(userAgent)) {
                    connection.setRequestProperty("User-Agent", userAgent);
                }

                applySafeHeaders(connection, options.optJSONObject("headers"));

                try (OutputStream output = new BufferedOutputStream(connection.getOutputStream())) {
                    output.write(bodyBytes);
                    output.flush();
                }

                int status = connection.getResponseCode();

                if (status < 200 || status >= 300) {
                    if (status >= 300 && status < 400) {
                        callbackContext.error(
                                "Download dialihkan oleh server. Sesi mungkin sudah berakhir."
                        );
                    } else {
                        callbackContext.error(
                                "Server menolak download (HTTP " + status + ")."
                        );
                    }
                    return;
                }

                String contentDisposition = connection.getHeaderField("Content-Disposition");
                if (TextUtils.isEmpty(contentDisposition)
                        || !contentDisposition.toLowerCase().contains("attachment")) {
                    callbackContext.error(
                            "Server tidak mengembalikan attachment yang valid."
                    );
                    return;
                }

                String mimeType = normalizeMimeType(connection.getContentType());
                String fileName = URLUtil.guessFileName(
                        url,
                        contentDisposition,
                        mimeType
                );
                fileName = sanitizeFileName(fileName);

                try (InputStream input = new BufferedInputStream(connection.getInputStream())) {
                    saveToDownloads(input, fileName, mimeType);
                }

                JSONObject result = new JSONObject();
                result.put("fileName", fileName);
                result.put("mimeType", mimeType);
                result.put("mode", "post");
                callbackContext.success(result);
            } catch (Exception error) {
                callbackContext.error(
                        messageFor(error, "POST download gagal diproses.")
                );
            } finally {
                if (connection != null) {
                    connection.disconnect();
                }
            }
        });
    }

    private String encodeFields(JSONArray fields) throws Exception {
        if (fields == null) {
            return "";
        }

        if (fields.length() > MAX_POST_FIELDS) {
            throw new IllegalArgumentException("Terlalu banyak field pada request download.");
        }

        StringBuilder encoded = new StringBuilder();

        for (int i = 0; i < fields.length(); i++) {
            JSONObject field = fields.optJSONObject(i);
            if (field == null) {
                continue;
            }

            String name = field.optString("name", "");
            if (TextUtils.isEmpty(name) || name.length() > 128) {
                throw new IllegalArgumentException("Nama field download tidak valid.");
            }

            String value = field.optString("value", "");

            if (encoded.length() > 0) {
                encoded.append('&');
            }

            encoded
                    .append(URLEncoder.encode(name, StandardCharsets.UTF_8.name()))
                    .append('=')
                    .append(URLEncoder.encode(value, StandardCharsets.UTF_8.name()));

            if (encoded.length() > MAX_POST_BODY_BYTES) {
                throw new IllegalArgumentException("Payload export terlalu besar.");
            }
        }

        return encoded.toString();
    }

    private void applySafeHeaders(HttpURLConnection connection, JSONObject headers) {
        if (headers == null) {
            return;
        }

        Iterator<String> keys = headers.keys();

        while (keys.hasNext()) {
            String key = keys.next();
            String normalized = key == null ? "" : key.trim();

            if (
                    !"X-CSRF-TOKEN".equalsIgnoreCase(normalized)
                    && !"Accept".equalsIgnoreCase(normalized)
                    && !"X-Requested-With".equalsIgnoreCase(normalized)
            ) {
                continue;
            }

            String value = headers.optString(key, "");
            if (!TextUtils.isEmpty(value) && value.length() <= 8192) {
                connection.setRequestProperty(normalized, value);
            }
        }
    }

    private void saveToDownloads(InputStream input, String fileName, String mimeType) throws Exception {
        Context context = cordova.getActivity().getApplicationContext();

        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.Q) {
            ContentResolver resolver = context.getContentResolver();
            ContentValues values = new ContentValues();
            values.put(MediaStore.MediaColumns.DISPLAY_NAME, fileName);
            values.put(
                    MediaStore.MediaColumns.MIME_TYPE,
                    TextUtils.isEmpty(mimeType)
                            ? "application/octet-stream"
                            : mimeType
            );
            values.put(
                    MediaStore.MediaColumns.RELATIVE_PATH,
                    Environment.DIRECTORY_DOWNLOADS
            );
            values.put(MediaStore.MediaColumns.IS_PENDING, 1);

            Uri item = resolver.insert(
                    MediaStore.Downloads.EXTERNAL_CONTENT_URI,
                    values
            );

            if (item == null) {
                throw new IllegalStateException("Android gagal membuat file Downloads.");
            }

            boolean completed = false;

            try {
                OutputStream rawOutput = resolver.openOutputStream(item);

                if (rawOutput == null) {
                    throw new IllegalStateException("Android gagal membuka file Downloads.");
                }

                try (OutputStream output = new BufferedOutputStream(rawOutput)) {
                    copy(input, output);
                }

                ContentValues completedValues = new ContentValues();
                completedValues.put(MediaStore.MediaColumns.IS_PENDING, 0);
                resolver.update(item, completedValues, null, null);
                completed = true;
            } finally {
                if (!completed) {
                    resolver.delete(item, null, null);
                }
            }

            return;
        }

        File downloads = Environment.getExternalStoragePublicDirectory(
                Environment.DIRECTORY_DOWNLOADS
        );

        if (!downloads.exists() && !downloads.mkdirs()) {
            throw new IllegalStateException("Folder Downloads tidak dapat dibuat.");
        }

        File target = uniqueFile(downloads, fileName);

        try (OutputStream output = new BufferedOutputStream(new FileOutputStream(target))) {
            copy(input, output);
        }

        MediaScannerConnection.scanFile(
                context,
                new String[]{target.getAbsolutePath()},
                new String[]{
                        TextUtils.isEmpty(mimeType)
                                ? "application/octet-stream"
                                : mimeType
                },
                null
        );
    }

    private void copy(InputStream input, OutputStream output) throws Exception {
        byte[] buffer = new byte[32 * 1024];
        int read;

        while ((read = input.read(buffer)) != -1) {
            output.write(buffer, 0, read);
        }

        output.flush();
    }

    private File uniqueFile(File directory, String fileName) {
        File candidate = new File(directory, fileName);

        if (!candidate.exists()) {
            return candidate;
        }

        int dot = fileName.lastIndexOf('.');
        String base = dot > 0 ? fileName.substring(0, dot) : fileName;
        String extension = dot > 0 ? fileName.substring(dot) : "";

        for (int index = 1; index <= 999; index++) {
            candidate = new File(
                    directory,
                    base + " (" + index + ")" + extension
            );

            if (!candidate.exists()) {
                return candidate;
            }
        }

        return new File(
                directory,
                base + "-" + System.currentTimeMillis() + extension
        );
    }

    private boolean isAllowedUrl(String value) {
        URI uri = parseAllowedUri(value);
        return uri != null;
    }

    private boolean isAllowedPostUrl(String value) {
        URI uri = parseAllowedUri(value);

        return uri != null
                && POST_DOWNLOAD_PATHS.contains(uri.getPath());
    }

    private URI parseAllowedUri(String value) {
        try {
            URI uri = new URI(value);
            int port = uri.getPort();

            if (
                    !"https".equalsIgnoreCase(uri.getScheme())
                    || !APP_HOST.equalsIgnoreCase(uri.getHost())
                    || (port != -1 && port != 443)
                    || uri.getUserInfo() != null
            ) {
                return null;
            }

            return uri;
        } catch (URISyntaxException error) {
            return null;
        }
    }

    private String normalizeMimeType(String value) {
        if (value == null) {
            return "";
        }

        int separator = value.indexOf(';');
        String mime = separator >= 0
                ? value.substring(0, separator)
                : value;

        return mime.trim();
    }

    private String sanitizeFileName(String value) {
        String safe = value == null ? "sisfour-download" : value.trim();
        safe = safe.replaceAll("[\\/:*?\"<>|\\r\\n]+", "_");
        safe = safe.replaceAll("^\\.+", "");

        if (safe.isEmpty()) {
            return "sisfour-download";
        }

        return safe;
    }

    private String messageFor(Exception error, String fallback) {
        String message = error.getMessage();

        return TextUtils.isEmpty(message)
                ? fallback
                : message;
    }
}
