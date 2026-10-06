package id.sch.mtsn4jombang.sisfour.nativebridge;

import android.app.Activity;
import android.content.ContentResolver;
import android.content.Context;
import android.content.Intent;
import android.database.Cursor;
import android.net.Uri;
import android.provider.OpenableColumns;
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
import java.net.URLEncoder;
import java.nio.charset.StandardCharsets;
import java.util.List;
import java.util.Locale;
import java.util.Map;

public class SisFourNative extends CordovaPlugin {
    private static final String ACTION_DOWNLOAD = "download";
    private static final String ACTION_DOWNLOAD_REQUEST = "downloadRequest";
    private static final String APP_HOST = "sisfour.mtsn4jombang.sch.id";
    private static final int REQUEST_CREATE_DOCUMENT = 4102;
    private static final int MAX_FORM_FIELDS = 1200;
    private static final int MAX_FORM_BODY_BYTES = 24 * 1024 * 1024;

    private File pendingSaveFile;
    private String pendingSaveName;
    private String pendingSaveMime;
    private CallbackContext pendingSaveCallback;

    @Override
    public boolean execute(
            String action,
            JSONArray args,
            CallbackContext callbackContext
    ) throws JSONException {
        JSONObject options =
                args.optJSONObject(0);

        if (options == null) {
            callbackContext.error(
                    "Payload download tidak valid."
            );
            return true;
        }

        String url =
                options.optString(
                        "url",
                        ""
                );

        if (!isAllowedUrl(url)) {
            callbackContext.error(
                    "URL download tidak diizinkan."
            );
            return true;
        }

        if (ACTION_DOWNLOAD.equals(action)) {
            downloadGet(
                    options,
                    callbackContext
            );
            return true;
        }

        if (
                ACTION_DOWNLOAD_REQUEST
                        .equals(action)
        ) {
            String method =
                    options.optString(
                            "method",
                            ""
                    )
                            .trim()
                            .toUpperCase(
                                    Locale.ROOT
                            );

            if (!"POST".equals(method)) {
                callbackContext.error(
                        "Method download native tidak diizinkan."
                );
                return true;
            }

            downloadPost(
                    options,
                    callbackContext
            );
            return true;
        }

        return false;
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

                processAttachmentResponse(
                        connection,
                        url,
                        eventDisposition,
                        eventMimeType,
                        callbackContext
                );
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

    private void downloadPost(
            JSONObject options,
            CallbackContext callbackContext
    ) {
        cordova.getThreadPool().execute(() -> {
            HttpURLConnection connection = null;

            try {
                String url =
                        options.optString(
                                "url",
                                ""
                        );

                String userAgent =
                        options.optString(
                                "userAgent",
                                ""
                        );

                byte[] body =
                        buildFormBody(
                                options.optJSONArray(
                                        "fields"
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

                connection.setRequestMethod("POST");
                connection.setConnectTimeout(20000);
                connection.setReadTimeout(180000);
                connection.setDoOutput(true);

                connection.setRequestProperty(
                        "Content-Type",
                        "application/x-www-form-urlencoded; charset=UTF-8"
                );

                applySafeHeaders(
                        connection,
                        options.optJSONObject(
                                "headers"
                        )
                );

                if (
                        TextUtils.isEmpty(
                                connection.getRequestProperty(
                                        "Accept"
                                )
                        )
                ) {
                    connection.setRequestProperty(
                            "Accept",
                            "*/*"
                    );
                }

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

                connection.setFixedLengthStreamingMode(
                        body.length
                );

                try (
                        OutputStream output =
                                new BufferedOutputStream(
                                        connection
                                                .getOutputStream()
                                )
                ) {
                    output.write(body);
                    output.flush();
                }

                processAttachmentResponse(
                        connection,
                        url,
                        "",
                        "",
                        callbackContext
                );
            } catch (Exception error) {
                callbackContext.error(
                        messageFor(
                                error,
                                "POST download gagal diproses."
                        )
                );
            } finally {
                if (connection != null) {
                    connection.disconnect();
                }
            }
        });
    }

    private byte[] buildFormBody(
            JSONArray fields
    ) throws Exception {
        if (fields == null) {
            return new byte[0];
        }

        if (fields.length() > MAX_FORM_FIELDS) {
            throw new IllegalArgumentException(
                    "Jumlah field POST terlalu banyak."
            );
        }

        StringBuilder body =
                new StringBuilder();

        for (
                int index = 0;
                index < fields.length();
                index++
        ) {
            JSONObject field =
                    fields.optJSONObject(index);

            if (field == null) {
                throw new IllegalArgumentException(
                        "Field POST tidak valid."
                );
            }

            String name =
                    field.optString(
                            "name",
                            ""
                    );

            String value =
                    field.optString(
                            "value",
                            ""
                    );

            if (
                    TextUtils.isEmpty(name)
                    || name.length() > 256
                    || value.length()
                            > 12 * 1024 * 1024
            ) {
                throw new IllegalArgumentException(
                        "Ukuran field POST tidak valid."
                );
            }

            if (index > 0) {
                body.append('&');
            }

            body.append(
                    URLEncoder.encode(
                            name,
                            StandardCharsets.UTF_8
                                    .name()
                    )
            );

            body.append('=');

            body.append(
                    URLEncoder.encode(
                            value,
                            StandardCharsets.UTF_8
                                    .name()
                    )
            );

            if (
                    body.length()
                    > MAX_FORM_BODY_BYTES
            ) {
                throw new IllegalArgumentException(
                        "Payload POST file terlalu besar."
                );
            }
        }

        byte[] encoded =
                body.toString()
                        .getBytes(
                                StandardCharsets.UTF_8
                        );

        if (
                encoded.length
                > MAX_FORM_BODY_BYTES
        ) {
            throw new IllegalArgumentException(
                    "Payload POST file terlalu besar."
            );
        }

        return encoded;
    }

    private void applySafeHeaders(
            HttpURLConnection connection,
            JSONObject headers
    ) {
        if (headers == null) {
            return;
        }

        applyHeaderIfSafe(
                connection,
                "Accept",
                headers.optString(
                        "Accept",
                        ""
                )
        );

        applyHeaderIfSafe(
                connection,
                "X-Requested-With",
                headers.optString(
                        "X-Requested-With",
                        ""
                )
        );
    }

    private void applyHeaderIfSafe(
            HttpURLConnection connection,
            String name,
            String value
    ) {
        String safe =
                value == null
                        ? ""
                        : value.trim();

        if (
                safe.isEmpty()
                || safe.length() > 1024
                || safe.contains("\r")
                || safe.contains("\n")
        ) {
            return;
        }

        connection.setRequestProperty(
                name,
                safe
        );
    }

    private void processAttachmentResponse(
            HttpURLConnection connection,
            String url,
            String eventDisposition,
            String eventMimeType,
            CallbackContext callbackContext
    ) throws Exception {
        int status =
                connection.getResponseCode();

        syncResponseCookies(
                connection,
                url
        );

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
                        .toLowerCase(
                                Locale.ROOT
                        )
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
                TextUtils.isEmpty(
                        serverFileName
                )
                        ? URLUtil.guessFileName(
                                url,
                                contentDisposition,
                                mimeType
                        )
                        : serverFileName;

        fileName =
                sanitizeFileName(
                        fileName
                );

        mimeType =
                resolveMimeType(
                        mimeType,
                        fileName
                );

        File tempFile =
                createTempDownloadFile(
                        fileName
                );

        boolean tempReady = false;

        try (
                InputStream input =
                        new BufferedInputStream(
                                connection
                                        .getInputStream()
                        );
                OutputStream output =
                        new BufferedOutputStream(
                                new FileOutputStream(
                                        tempFile
                                )
                        )
        ) {
            copy(input, output);
            tempReady = true;
        } finally {
            if (!tempReady) {
                deleteQuietly(tempFile);
            }
        }

        promptSaveAs(
                tempFile,
                fileName,
                mimeType,
                callbackContext
        );
    }

    private void syncResponseCookies(
            HttpURLConnection connection,
            String url
    ) {
        Map<String, List<String>> headers =
                connection.getHeaderFields();

        if (headers == null || headers.isEmpty()) {
            return;
        }

        CookieManager cookieManager =
                CookieManager.getInstance();

        boolean changed = false;

        for (
                Map.Entry<String, List<String>> entry
                        : headers.entrySet()
        ) {
            String name =
                    entry.getKey();

            if (
                    name == null
                    || !"set-cookie"
                            .equalsIgnoreCase(name)
            ) {
                continue;
            }

            List<String> values =
                    entry.getValue();

            if (values == null) {
                continue;
            }

            for (String value : values) {
                if (TextUtils.isEmpty(value)) {
                    continue;
                }

                cookieManager.setCookie(
                        url,
                        value
                );

                changed = true;
            }
        }

        if (changed) {
            cookieManager.flush();
        }
    }

    private File createTempDownloadFile(
            String fileName
    ) throws Exception {
        Context context =
                cordova
                        .getActivity()
                        .getApplicationContext();

        File directory =
                new File(
                        context.getCacheDir(),
                        "sisfour-downloads"
                );

        if (
                !directory.exists()
                && !directory.mkdirs()
                && !directory.isDirectory()
        ) {
            throw new IllegalStateException(
                    "Folder sementara download tidak dapat dibuat."
            );
        }

        String suffix = ".tmp";
        int dot =
                fileName == null
                        ? -1
                        : fileName.lastIndexOf('.');

        if (
                dot >= 0
                && dot < fileName.length() - 1
        ) {
            String extension =
                    fileName.substring(dot);

            if (
                    extension.matches(
                            "\\.[A-Za-z0-9]{1,12}"
                    )
            ) {
                suffix = extension;
            }
        }

        return File.createTempFile(
                "sisfour_",
                suffix,
                directory
        );
    }

    private void promptSaveAs(
            File tempFile,
            String fileName,
            String mimeType,
            CallbackContext callbackContext
    ) {
        synchronized (this) {
            if (pendingSaveCallback != null) {
                deleteQuietly(tempFile);
                callbackContext.error(
                        "Masih ada proses penyimpanan file yang belum selesai."
                );
                return;
            }

            pendingSaveFile = tempFile;
            pendingSaveName = fileName;
            pendingSaveMime =
                    TextUtils.isEmpty(mimeType)
                            ? "application/octet-stream"
                            : mimeType;
            pendingSaveCallback = callbackContext;
        }

        cordova.getActivity().runOnUiThread(() -> {
            try {
                Intent intent =
                        new Intent(
                                Intent.ACTION_CREATE_DOCUMENT
                        );

                intent.addCategory(
                        Intent.CATEGORY_OPENABLE
                );

                intent.setType(
                        TextUtils.isEmpty(
                                pendingSaveMime
                        )
                                ? "application/octet-stream"
                                : pendingSaveMime
                );

                intent.putExtra(
                        Intent.EXTRA_TITLE,
                        pendingSaveName
                );

                cordova.startActivityForResult(
                        this,
                        intent,
                        REQUEST_CREATE_DOCUMENT
                );
            } catch (Exception error) {
                PendingSave pending =
                        takePendingSave();

                if (pending != null) {
                    deleteQuietly(
                            pending.tempFile
                    );

                    pending.callback.error(
                            messageFor(
                                    error,
                                    "Pemilih lokasi penyimpanan Android tidak dapat dibuka."
                            )
                    );
                }
            }
        });
    }

    @Override
    public void onActivityResult(
            int requestCode,
            int resultCode,
            Intent intent
    ) {
        if (
                requestCode
                != REQUEST_CREATE_DOCUMENT
        ) {
            return;
        }

        PendingSave pending =
                takePendingSave();

        if (pending == null) {
            return;
        }

        if (
                resultCode
                        != Activity.RESULT_OK
                || intent == null
                || intent.getData() == null
        ) {
            deleteQuietly(
                    pending.tempFile
            );

            pending.callback.error(
                    "Penyimpanan file dibatalkan."
            );
            return;
        }

        Uri destination =
                intent.getData();

        cordova.getThreadPool().execute(() -> {
            try {
                ContentResolver resolver =
                        cordova
                                .getActivity()
                                .getContentResolver();

                OutputStream rawOutput =
                        resolver.openOutputStream(
                                destination,
                                "w"
                        );

                if (rawOutput == null) {
                    throw new IllegalStateException(
                            "Android gagal membuka lokasi file yang dipilih."
                    );
                }

                try (
                        InputStream input =
                                new BufferedInputStream(
                                        new java.io.FileInputStream(
                                                pending.tempFile
                                        )
                                );
                        OutputStream output =
                                new BufferedOutputStream(
                                        rawOutput
                                )
                ) {
                    copy(input, output);
                }

                String actualName =
                        displayNameForUri(
                                resolver,
                                destination
                        );

                if (TextUtils.isEmpty(actualName)) {
                    actualName =
                            pending.fileName;
                }

                JSONObject result =
                        new JSONObject();

                result.put(
                        "fileName",
                        actualName
                );

                result.put(
                        "mimeType",
                        pending.mimeType
                );

                pending.callback.success(
                        result
                );
            } catch (Exception error) {
                pending.callback.error(
                        messageFor(
                                error,
                                "File gagal disimpan ke lokasi yang dipilih."
                        )
                );
            } finally {
                deleteQuietly(
                        pending.tempFile
                );
            }
        });
    }

    private synchronized PendingSave takePendingSave() {
        if (pendingSaveCallback == null) {
            return null;
        }

        PendingSave pending =
                new PendingSave(
                        pendingSaveFile,
                        pendingSaveName,
                        pendingSaveMime,
                        pendingSaveCallback
                );

        pendingSaveFile = null;
        pendingSaveName = null;
        pendingSaveMime = null;
        pendingSaveCallback = null;

        return pending;
    }

    private String displayNameForUri(
            ContentResolver resolver,
            Uri uri
    ) {
        try (
                Cursor cursor =
                        resolver.query(
                                uri,
                                new String[]{
                                    OpenableColumns.DISPLAY_NAME
                                },
                                null,
                                null,
                                null
                        )
        ) {
            if (
                    cursor != null
                    && cursor.moveToFirst()
            ) {
                int index =
                        cursor.getColumnIndex(
                                OpenableColumns.DISPLAY_NAME
                        );

                if (index >= 0) {
                    return cursor.getString(
                            index
                    );
                }
            }
        } catch (Exception ignored) {
            // The selected provider may not expose a display name.
        }

        return "";
    }

    private void deleteQuietly(
            File file
    ) {
        if (
                file != null
                && file.exists()
        ) {
            try {
                file.delete();
            } catch (Exception ignored) {
                // App cache cleanup failure is non-fatal.
            }
        }
    }

    private static final class PendingSave {
        final File tempFile;
        final String fileName;
        final String mimeType;
        final CallbackContext callback;

        PendingSave(
                File tempFile,
                String fileName,
                String mimeType,
                CallbackContext callback
        ) {
            this.tempFile = tempFile;
            this.fileName = fileName;
            this.mimeType = mimeType;
            this.callback = callback;
        }
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

            String canonical =
                    canonicalMimeForExtension(
                            extension
                    );

            if (!TextUtils.isEmpty(canonical)) {
                return canonical;
            }
        }

        return TextUtils.isEmpty(normalized)
                ? "application/octet-stream"
                : normalized;
    }

    private String canonicalMimeForExtension(
            String extension
    ) {
        if (extension == null) {
            return "";
        }

        String normalized =
                extension.toLowerCase(
                        Locale.ROOT
                );

        switch (normalized) {
            case "xlsx":
                return "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet";
            case "pdf":
                return "application/pdf";
            case "zip":
                return "application/zip";
            case "png":
                return "image/png";
            case "jpg":
            case "jpeg":
                return "image/jpeg";
            case "sql":
                return "application/sql";
            default:
                return "";
        }
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
