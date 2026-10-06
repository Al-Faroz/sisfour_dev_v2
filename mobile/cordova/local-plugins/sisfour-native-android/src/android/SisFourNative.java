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
import android.webkit.MimeTypeMap;
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
import java.net.URLDecoder;
import java.nio.charset.StandardCharsets;
import java.util.Locale;

public class SisFourNative extends CordovaPlugin {
    private static final String ACTION_DOWNLOAD = "download";
    private static final String APP_HOST = "sisfour.mtsn4jombang.sch.id";
    private static final int REQUEST_WRITE_STORAGE = 4101;

    private JSONObject pendingDownload;
    private CallbackContext pendingDownloadCallback;

    @Override
    public boolean execute(
            String action,
            JSONArray args,
            CallbackContext callbackContext
    ) throws JSONException {
        if (!ACTION_DOWNLOAD.equals(action)) {
            return false;
        }

        JSONObject options = args.optJSONObject(0);

        if (options == null) {
            callbackContext.error(
                    "Payload download tidak valid."
            );
            return true;
        }

        String url = options.optString("url", "");

        if (!isAllowedUrl(url)) {
            callbackContext.error(
                    "URL download tidak diizinkan."
            );
            return true;
        }

        if (
                requiresLegacyStoragePermission()
                && !cordova.hasPermission(
                        Manifest.permission.WRITE_EXTERNAL_STORAGE
                )
        ) {
            pendingDownload = options;
            pendingDownloadCallback = callbackContext;

            cordova.requestPermission(
                    this,
                    REQUEST_WRITE_STORAGE,
                    Manifest.permission.WRITE_EXTERNAL_STORAGE
            );

            return true;
        }

        downloadGet(
                options,
                callbackContext
        );

        return true;
    }

    @Override
    public void onRequestPermissionsResult(
            int requestCode,
            String[] permissions,
            int[] grantResults
    ) throws JSONException {
        if (requestCode != REQUEST_WRITE_STORAGE) {
            return;
        }

        JSONObject options = pendingDownload;
        CallbackContext callbackContext =
                pendingDownloadCallback;

        pendingDownload = null;
        pendingDownloadCallback = null;

        if (
                callbackContext == null
                || options == null
        ) {
            return;
        }

        boolean granted =
                grantResults.length > 0
                && grantResults[0]
                    == PackageManager.PERMISSION_GRANTED;

        if (!granted) {
            callbackContext.error(
                    "Izin penyimpanan ditolak."
            );
            return;
        }

        downloadGet(
                options,
                callbackContext
        );
    }

    private boolean requiresLegacyStoragePermission() {
        return Build.VERSION.SDK_INT
                <= Build.VERSION_CODES.P;
    }

    private void downloadGet(
            JSONObject options,
            CallbackContext callbackContext
    ) {
        cordova.getThreadPool().execute(() -> {
            HttpURLConnection connection = null;

            try {
                String url =
                        options.optString("url", "");

                String userAgent =
                        options.optString(
                                "userAgent",
                                ""
                        );

                String eventDisposition =
                        options.optString(
                                "contentDisposition",
                                ""
                        );

                String eventMimeType =
                        normalizeMimeType(
                                options.optString(
                                        "mimetype",
                                        ""
                                )
                        );

                String cookie =
                        CookieManager
                                .getInstance()
                                .getCookie(url);

                connection =
                        (HttpURLConnection)
                                new URL(url)
                                        .openConnection();

                connection.setInstanceFollowRedirects(
                        false
                );

                connection.setRequestMethod("GET");
                connection.setConnectTimeout(20000);
                connection.setReadTimeout(120000);
                connection.setRequestProperty(
                        "Accept",
                        "*/*"
                );

                if (!TextUtils.isEmpty(cookie)) {
                    connection.setRequestProperty(
                            "Cookie",
                            cookie
                    );
                }

                if (!TextUtils.isEmpty(userAgent)) {
                    connection.setRequestProperty(
                            "User-Agent",
                            userAgent
                    );
                }

                int status =
                        connection.getResponseCode();

                if (status < 200 || status >= 300) {
                    if (
                            status >= 300
                            && status < 400
                    ) {
                        callbackContext.error(
                                "Download dialihkan oleh server. Sesi mungkin sudah berakhir."
                        );
                    } else {
                        callbackContext.error(
                                "Server menolak download (HTTP "
                                        + status
                                        + ")."
                        );
                    }

                    return;
                }

                String responseDisposition =
                        connection.getHeaderField(
                                "Content-Disposition"
                        );

                String contentDisposition =
                        TextUtils.isEmpty(
                                responseDisposition
                        )
                                ? eventDisposition
                                : responseDisposition;

                String responseMimeType =
                        normalizeMimeType(
                                connection
                                        .getContentType()
                        );

                String mimeType =
                        chooseMimeType(
                                responseMimeType,
                                eventMimeType
                        );

                if (
                        TextUtils.isEmpty(
                                contentDisposition
                        )
                        || !contentDisposition
                                .toLowerCase(Locale.ROOT)
                                .contains("attachment")
                ) {
                    callbackContext.error(
                            "Server tidak mengembalikan attachment yang valid."
                    );
                    return;
                }

                String serverFileName =
                        extractDownloadFileName(
                                contentDisposition
                        );

                String fileName =
                        TextUtils.isEmpty(serverFileName)
                                ? URLUtil.guessFileName(
                                        url,
                                        contentDisposition,
                                        mimeType
                                )
                                : serverFileName;

                fileName =
                        sanitizeFileName(fileName);

                mimeType =
                        resolveMimeType(
                                mimeType,
                                fileName
                        );

                try (
                        InputStream input =
                                new BufferedInputStream(
                                        connection
                                                .getInputStream()
                                )
                ) {
                    saveToDownloads(
                            input,
                            fileName,
                            mimeType
                    );
                }

                JSONObject result =
                        new JSONObject();

                result.put(
                        "fileName",
                        fileName
                );

                result.put(
                        "mimeType",
                        mimeType
                );

                callbackContext.success(result);
            } catch (Exception error) {
                callbackContext.error(
                        messageFor(
                                error,
                                "Download gagal diproses."
                        )
                );
            } finally {
                if (connection != null) {
                    connection.disconnect();
                }
            }
        });
    }

    private void saveToDownloads(
            InputStream input,
            String fileName,
            String mimeType
    ) throws Exception {
        Context context =
                cordova
                        .getActivity()
                        .getApplicationContext();

        if (
                Build.VERSION.SDK_INT
                >= Build.VERSION_CODES.Q
        ) {
            ContentResolver resolver =
                    context.getContentResolver();

            ContentValues values =
                    new ContentValues();

            values.put(
                    MediaStore.MediaColumns.DISPLAY_NAME,
                    fileName
            );

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

            values.put(
                    MediaStore.MediaColumns.IS_PENDING,
                    1
            );

            Uri item =
                    resolver.insert(
                            MediaStore.Downloads
                                    .EXTERNAL_CONTENT_URI,
                            values
                    );

            if (item == null) {
                throw new IllegalStateException(
                        "Android gagal membuat file Downloads."
                );
            }

            boolean completed = false;

            try {
                OutputStream rawOutput =
                        resolver.openOutputStream(
                                item
                        );

                if (rawOutput == null) {
                    throw new IllegalStateException(
                            "Android gagal membuka file Downloads."
                    );
                }

                try (
                        OutputStream output =
                                new BufferedOutputStream(
                                        rawOutput
                                )
                ) {
                    copy(input, output);
                }

                ContentValues done =
                        new ContentValues();

                done.put(
                        MediaStore.MediaColumns.IS_PENDING,
                        0
                );

                resolver.update(
                        item,
                        done,
                        null,
                        null
                );

                completed = true;
            } finally {
                if (!completed) {
                    resolver.delete(
                            item,
                            null,
                            null
                    );
                }
            }

            return;
        }

        File downloads =
                Environment
                        .getExternalStoragePublicDirectory(
                                Environment.DIRECTORY_DOWNLOADS
                        );

        if (
                !downloads.exists()
                && !downloads.mkdirs()
        ) {
            throw new IllegalStateException(
                    "Folder Downloads tidak dapat dibuat."
            );
        }

        File target =
                uniqueFile(
                        downloads,
                        fileName
                );

        try (
                OutputStream output =
                        new BufferedOutputStream(
                                new FileOutputStream(
                                        target
                                )
                        )
        ) {
            copy(input, output);
        }

        MediaScannerConnection.scanFile(
                context,
                new String[]{
                    target.getAbsolutePath()
                },
                new String[]{
                    TextUtils.isEmpty(mimeType)
                            ? "application/octet-stream"
                            : mimeType
                },
                null
        );
    }

    private void copy(
            InputStream input,
            OutputStream output
    ) throws Exception {
        byte[] buffer =
                new byte[32 * 1024];

        int read;

        while (
                (read = input.read(buffer))
                != -1
        ) {
            output.write(
                    buffer,
                    0,
                    read
            );
        }

        output.flush();
    }

    private File uniqueFile(
            File directory,
            String fileName
    ) {
        File candidate =
                new File(
                        directory,
                        fileName
                );

        if (!candidate.exists()) {
            return candidate;
        }

        int dot =
                fileName.lastIndexOf('.');

        String base =
                dot > 0
                        ? fileName.substring(
                                0,
                                dot
                        )
                        : fileName;

        String extension =
                dot > 0
                        ? fileName.substring(dot)
                        : "";

        for (
                int index = 1;
                index <= 999;
                index++
        ) {
            candidate =
                    new File(
                            directory,
                            base
                                    + " ("
                                    + index
                                    + ")"
                                    + extension
                    );

            if (!candidate.exists()) {
                return candidate;
            }
        }

        return new File(
                directory,
                base
                        + "-"
                        + System.currentTimeMillis()
                        + extension
        );
    }

    private boolean isAllowedUrl(
            String value
    ) {
        URI uri =
                parseAllowedUri(value);

        return uri != null;
    }

    private URI parseAllowedUri(
            String value
    ) {
        try {
            URI uri =
                    new URI(value);

            int port =
                    uri.getPort();

            if (
                    !"https".equalsIgnoreCase(
                            uri.getScheme()
                    )
                    || !APP_HOST.equalsIgnoreCase(
                            uri.getHost()
                    )
                    || (
                            port != -1
                            && port != 443
                    )
                    || uri.getUserInfo() != null
            ) {
                return null;
            }

            return uri;
        } catch (URISyntaxException error) {
            return null;
        }
    }

    private String extractDownloadFileName(
            String contentDisposition
    ) {
        if (TextUtils.isEmpty(contentDisposition)) {
            return "";
        }

        String fallback = "";

        String[] parts =
                contentDisposition.split(";");

        for (String rawPart : parts) {
            String part =
                    rawPart == null
                            ? ""
                            : rawPart.trim();

            int separator =
                    part.indexOf('=');

            if (separator <= 0) {
                continue;
            }

            String key =
                    part.substring(
                            0,
                            separator
                    )
                            .trim()
                            .toLowerCase(
                                    Locale.ROOT
                            );

            String value =
                    part.substring(
                            separator + 1
                    )
                            .trim();

            if (
                    value.length() >= 2
                    && (
                            (
                                    value.startsWith("\"")
                                    && value.endsWith("\"")
                            )
                            || (
                                    value.startsWith("'")
                                    && value.endsWith("'")
                            )
                    )
            ) {
                value =
                        value.substring(
                                1,
                                value.length() - 1
                        );
            }

            if ("filename*".equals(key)) {
                int charsetSeparator =
                        value.indexOf("''");

                String encoded =
                        charsetSeparator >= 0
                                ? value.substring(
                                        charsetSeparator + 2
                                )
                                : value;

                try {
                    String decoded =
                            URLDecoder.decode(
                                    encoded,
                                    StandardCharsets.UTF_8
                                            .name()
                            );

                    if (!TextUtils.isEmpty(decoded)) {
                        return decoded;
                    }
                } catch (Exception ignored) {
                    // Fall back to the regular filename parameter.
                }
            }

            if (
                    "filename".equals(key)
                    && !TextUtils.isEmpty(value)
            ) {
                fallback =
                        value.replace(
                                "\\\"",
                                "\""
                        );
            }
        }

        return fallback;
    }

    private String chooseMimeType(
            String responseMimeType,
            String eventMimeType
    ) {
        String response =
                normalizeMimeType(
                        responseMimeType
                );

        String event =
                normalizeMimeType(
                        eventMimeType
                );

        if (
                !TextUtils.isEmpty(response)
                && !"application/octet-stream"
                        .equalsIgnoreCase(response)
        ) {
            return response;
        }

        if (
                !TextUtils.isEmpty(event)
                && !"application/octet-stream"
                        .equalsIgnoreCase(event)
        ) {
            return event;
        }

        return response;
    }

    private String resolveMimeType(
            String mimeType,
            String fileName
    ) {
        String normalized =
                normalizeMimeType(mimeType);

        if (
                !TextUtils.isEmpty(normalized)
                && !"application/octet-stream"
                        .equalsIgnoreCase(normalized)
        ) {
            return normalized;
        }

        int dot =
                fileName == null
                        ? -1
                        : fileName.lastIndexOf('.');

        if (
                dot >= 0
                && dot < fileName.length() - 1
        ) {
            String extension =
                    fileName.substring(
                            dot + 1
                    )
                            .toLowerCase(
                                    Locale.ROOT
                            );

            String inferred =
                    MimeTypeMap
                            .getSingleton()
                            .getMimeTypeFromExtension(
                                    extension
                            );

            if (!TextUtils.isEmpty(inferred)) {
                return inferred;
            }
        }

        return TextUtils.isEmpty(normalized)
                ? "application/octet-stream"
                : normalized;
    }

    private String normalizeMimeType(
            String value
    ) {
        if (value == null) {
            return "";
        }

        int separator =
                value.indexOf(';');

        String mime =
                separator >= 0
                        ? value.substring(
                                0,
                                separator
                        )
                        : value;

        return mime.trim();
    }

    private String sanitizeFileName(
            String value
    ) {
        String safe =
                value == null
                        ? "sisfour-download"
                        : value.trim();

        safe =
                safe.replaceAll(
                        "[\\/:*?\"<>|\\r\\n]+",
                        "_"
                );

        safe =
                safe.replaceAll(
                        "^\\.+",
                        ""
                );

        if (safe.isEmpty()) {
            return "sisfour-download";
        }

        return safe;
    }

    private String messageFor(
            Exception error,
            String fallback
    ) {
        String message =
                error.getMessage();

        return TextUtils.isEmpty(message)
                ? fallback
                : message;
    }
}
