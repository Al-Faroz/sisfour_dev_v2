package id.sch.mtsn4jombang.sisfour.nativebridge;

import android.Manifest;
import android.app.DownloadManager;
import android.content.Context;
import android.content.pm.PackageManager;
import android.net.Uri;
import android.os.Build;
import android.os.Environment;
import android.text.TextUtils;
import android.webkit.CookieManager;
import android.webkit.URLUtil;

import org.apache.cordova.CallbackContext;
import org.apache.cordova.CordovaPlugin;
import org.json.JSONArray;
import org.json.JSONException;
import org.json.JSONObject;

import java.net.URI;
import java.net.URISyntaxException;

public class SisFourNative extends CordovaPlugin {
    private static final String ACTION_DOWNLOAD = "download";
    private static final String APP_HOST = "sisfour.mtsn4jombang.sch.id";
    private static final int REQUEST_WRITE_STORAGE = 4101;

    private JSONObject pendingDownload;
    private CallbackContext pendingDownloadCallback;

    @Override
    public boolean execute(String action, JSONArray args, CallbackContext callbackContext) throws JSONException {
        if (!ACTION_DOWNLOAD.equals(action)) {
            return false;
        }

        JSONObject options = args.optJSONObject(0);
        if (options == null) {
            callbackContext.error("Payload download tidak valid.");
            return true;
        }

        if (!isAllowedUrl(options.optString("url", ""))) {
            callbackContext.error("URL download tidak diizinkan.");
            return true;
        }

        if (Build.VERSION.SDK_INT <= Build.VERSION_CODES.P
                && !cordova.hasPermission(Manifest.permission.WRITE_EXTERNAL_STORAGE)) {
            pendingDownload = options;
            pendingDownloadCallback = callbackContext;
            cordova.requestPermission(this, REQUEST_WRITE_STORAGE, Manifest.permission.WRITE_EXTERNAL_STORAGE);
            return true;
        }

        enqueueDownload(options, callbackContext);
        return true;
    }

    @Override
    public void onRequestPermissionsResult(int requestCode, String[] permissions, int[] grantResults)
            throws JSONException {
        if (requestCode != REQUEST_WRITE_STORAGE) {
            return;
        }

        JSONObject options = pendingDownload;
        CallbackContext callbackContext = pendingDownloadCallback;
        pendingDownload = null;
        pendingDownloadCallback = null;

        if (callbackContext == null || options == null) {
            return;
        }

        boolean granted = grantResults.length > 0
                && grantResults[0] == PackageManager.PERMISSION_GRANTED;

        if (!granted) {
            callbackContext.error("Izin penyimpanan ditolak.");
            return;
        }

        enqueueDownload(options, callbackContext);
    }

    private void enqueueDownload(JSONObject options, CallbackContext callbackContext) {
        cordova.getThreadPool().execute(() -> {
            try {
                String url = options.optString("url", "");
                String userAgent = options.optString("userAgent", "");
                String contentDisposition = options.optString("contentDisposition", "");
                String mimeType = options.optString("mimetype", "");
                String cookie = CookieManager.getInstance().getCookie(url);
                String fileName = URLUtil.guessFileName(url, contentDisposition, mimeType);
                fileName = sanitizeFileName(fileName);

                DownloadManager.Request request = new DownloadManager.Request(Uri.parse(url));
                request.setTitle(fileName);
                request.setDescription("Mengunduh dari SisFour");
                request.setNotificationVisibility(
                        DownloadManager.Request.VISIBILITY_VISIBLE_NOTIFY_COMPLETED
                );
                request.setAllowedOverMetered(true);
                request.setAllowedOverRoaming(true);
                request.setDestinationInExternalPublicDir(Environment.DIRECTORY_DOWNLOADS, fileName);

                if (!TextUtils.isEmpty(mimeType)) {
                    request.setMimeType(mimeType);
                }

                if (!TextUtils.isEmpty(cookie)) {
                    request.addRequestHeader("Cookie", cookie);
                }

                if (!TextUtils.isEmpty(userAgent)) {
                    request.addRequestHeader("User-Agent", userAgent);
                }

                DownloadManager downloadManager = (DownloadManager) cordova.getActivity()
                        .getSystemService(Context.DOWNLOAD_SERVICE);

                if (downloadManager == null) {
                    callbackContext.error("Layanan download Android tidak tersedia.");
                    return;
                }

                long downloadId = downloadManager.enqueue(request);

                JSONObject result = new JSONObject();
                result.put("downloadId", downloadId);
                result.put("fileName", fileName);
                callbackContext.success(result);
            } catch (Exception error) {
                callbackContext.error(
                        error.getMessage() == null
                                ? "Download gagal dimulai."
                                : error.getMessage()
                );
            }
        });
    }

    private boolean isAllowedUrl(String value) {
        try {
            URI uri = new URI(value);
            return "https".equalsIgnoreCase(uri.getScheme())
                    && APP_HOST.equalsIgnoreCase(uri.getHost());
        } catch (URISyntaxException error) {
            return false;
        }
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
}
