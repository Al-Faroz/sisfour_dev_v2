package id.sch.mtsn4jombang.sisfour;

import android.Manifest;
import android.app.Activity;
import android.app.Application;
import android.content.ContentValues;
import android.content.Context;
import android.content.Intent;
import android.content.pm.PackageManager;
import android.graphics.Bitmap;
import android.media.MediaScannerConnection;
import android.net.Uri;
import android.os.Build;
import android.os.Bundle;
import android.os.Environment;
import android.provider.MediaStore;
import android.text.TextUtils;
import android.view.View;
import android.view.ViewGroup;
import android.webkit.CookieManager;
import android.webkit.JavascriptInterface;
import android.webkit.MimeTypeMap;
import android.webkit.URLUtil;
import android.webkit.WebResourceError;
import android.webkit.WebResourceRequest;
import android.webkit.WebView;
import android.webkit.WebViewClient;
import android.widget.Toast;

import androidx.activity.ComponentActivity;
import androidx.activity.OnBackPressedCallback;
import androidx.core.app.ActivityCompat;
import androidx.core.content.ContextCompat;

import org.json.JSONArray;
import org.json.JSONObject;

import java.io.BufferedInputStream;
import java.io.BufferedOutputStream;
import java.io.File;
import java.io.FileOutputStream;
import java.io.InputStream;
import java.io.OutputStream;
import java.net.HttpURLConnection;
import java.net.URL;
import java.net.URLDecoder;
import java.net.URLEncoder;
import java.nio.charset.StandardCharsets;
import java.util.List;
import java.util.Locale;
import java.util.Map;
import java.util.WeakHashMap;
import java.util.concurrent.ExecutorService;
import java.util.concurrent.Executors;

public final class SisFourApplication extends Application implements Application.ActivityLifecycleCallbacks {

    private static final String SISFOUR_HOST = "sisfour.mtsn4jombang.sch.id";
    private static final int WRITE_STORAGE_REQUEST = 7041;
    private static final long EXIT_WINDOW_MS = 2000L;
    private static final int MAX_FORM_FIELDS = 1200;
    private static final int MAX_FORM_BODY_BYTES = 24 * 1024 * 1024;

    private static final WeakHashMap<WebView, Boolean> INSTALLED = new WeakHashMap<>();
    private static final WeakHashMap<Activity, DownloadRequestSpec> PENDING_DOWNLOADS = new WeakHashMap<>();
    private static final ExecutorService DOWNLOAD_EXECUTOR = Executors.newFixedThreadPool(2);

    @Override
    public void onCreate() {
        super.onCreate();
        registerActivityLifecycleCallbacks(this);
    }

    @Override
    public void onActivityResumed(Activity activity) {
        if (!isInAppBrowserActivity(activity)) {
            return;
        }

        WebView webView = findWebView(activity.findViewById(android.R.id.content));
        if (webView == null) {
            return;
        }

        installBrowserCompat(activity, webView);
        resumePendingDownloadIfPossible(activity, webView);
    }

    private static boolean isInAppBrowserActivity(Activity activity) {
        String name = activity.getClass().getName();
        return name.contains("osinappbrowser") && name.contains("OSIABWebViewActivity");
    }

    private static WebView findWebView(View view) {
        if (view instanceof WebView) {
            return (WebView) view;
        }

        if (view instanceof ViewGroup) {
            ViewGroup group = (ViewGroup) view;
            for (int i = 0; i < group.getChildCount(); i++) {
                WebView found = findWebView(group.getChildAt(i));
                if (found != null) {
                    return found;
                }
            }
        }

        return null;
    }

    private static synchronized void installBrowserCompat(Activity activity, WebView webView) {
        if (Boolean.TRUE.equals(INSTALLED.get(webView))) {
            return;
        }

        INSTALLED.put(webView, Boolean.TRUE);

        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
            webView.setImportantForAutofill(View.IMPORTANT_FOR_AUTOFILL_YES);
        }

        // Narrow JS interface. Every URL is revalidated natively.
        webView.addJavascriptInterface(
                new BrowserBridge(activity, webView),
                "SisFourNative"
        );

        installNavigationPolicy(activity, webView);
        installDownloadPolicy(activity, webView);
        installBackPolicy(activity, webView);

        if (isSisFourHttps(webView.getUrl())) {
            injectCompatJavaScript(webView);
        }
    }

    private static void installNavigationPolicy(Activity activity, WebView webView) {
        final WebViewClient delegate = webView.getWebViewClient();

        webView.setWebViewClient(new WebViewClient() {
            @Override
            public boolean shouldOverrideUrlLoading(WebView view, WebResourceRequest request) {
                Uri uri = request.getUrl();

                if (isExternalHttpUrl(uri)) {
                    openExternal(activity, uri);
                    return true;
                }

                return delegate != null && delegate.shouldOverrideUrlLoading(view, request);
            }

            @Override
            public void onPageStarted(WebView view, String url, Bitmap favicon) {
                if (delegate != null) {
                    delegate.onPageStarted(view, url, favicon);
                } else {
                    super.onPageStarted(view, url, favicon);
                }
            }

            @Override
            public void onPageFinished(WebView view, String url) {
                if (delegate != null) {
                    delegate.onPageFinished(view, url);
                } else {
                    super.onPageFinished(view, url);
                }

                if (isSisFourHttps(url)) {
                    injectCompatJavaScript(view);
                }
            }

            @Override
            public void onReceivedError(
                    WebView view,
                    WebResourceRequest request,
                    WebResourceError error
            ) {
                if (delegate != null) {
                    delegate.onReceivedError(view, request, error);
                } else {
                    super.onReceivedError(view, request, error);
                }
            }

            @Override
            public void doUpdateVisitedHistory(WebView view, String url, boolean isReload) {
                if (delegate != null) {
                    delegate.doUpdateVisitedHistory(view, url, isReload);
                } else {
                    super.doUpdateVisitedHistory(view, url, isReload);
                }
            }
        });
    }

    private static void installDownloadPolicy(Activity activity, WebView webView) {
        webView.setDownloadListener((url, userAgent, contentDisposition, mimeType, contentLength) -> {
            DownloadRequestSpec request = DownloadRequestSpec.get(
                    url,
                    mimeType,
                    contentDisposition,
                    userAgent
            );

            startDownload(activity, webView, request);
        });
    }

    private static void installBackPolicy(Activity activity, WebView webView) {
        if (!(activity instanceof ComponentActivity)) {
            return;
        }

        ComponentActivity componentActivity = (ComponentActivity) activity;

        componentActivity.getOnBackPressedDispatcher().addCallback(
                componentActivity,
                new OnBackPressedCallback(true) {
                    private long lastRootBackAt = 0L;

                    @Override
                    public void handleOnBackPressed() {
                        String currentUrl = webView.getUrl();

                        if (!isRootScreen(currentUrl) && webView.canGoBack()) {
                            lastRootBackAt = 0L;
                            webView.goBack();
                            return;
                        }

                        long now = System.currentTimeMillis();
                        if (now - lastRootBackAt <= EXIT_WINDOW_MS) {
                            lastRootBackAt = 0L;
                            activity.moveTaskToBack(true);
                            return;
                        }

                        lastRootBackAt = now;
                        Toast.makeText(
                                activity,
                                "Tekan sekali lagi untuk keluar dari SisFour",
                                Toast.LENGTH_SHORT
                        ).show();
                    }
                }
        );
    }

    private static boolean isRootScreen(String url) {
        if (isBlank(url)) {
            return true;
        }

        try {
            Uri uri = Uri.parse(url);

            if (!SISFOUR_HOST.equalsIgnoreCase(uri.getHost())) {
                return false;
            }

            String path = uri.getPath();

            if (path == null || path.isEmpty() || "/".equals(path)) {
                return true;
            }

            if (path.length() > 1 && path.endsWith("/")) {
                path = path.substring(0, path.length() - 1);
            }

            return "/auth/login".equalsIgnoreCase(path)
                    || "/dashboard".equalsIgnoreCase(path);
        } catch (Exception ignored) {
            return false;
        }
    }

    private static boolean isSisFourHttps(String url) {
        if (isBlank(url)) {
            return false;
        }

        try {
            Uri uri = Uri.parse(url);
            return "https".equalsIgnoreCase(uri.getScheme())
                    && SISFOUR_HOST.equalsIgnoreCase(uri.getHost())
                    && uri.getUserInfo() == null;
        } catch (Exception ignored) {
            return false;
        }
    }

    private static boolean isAllowedDownloadUrl(String rawUrl) {
        return isSisFourHttps(rawUrl);
    }

    private static boolean isExternalHttpUrl(Uri uri) {
        if (uri == null) {
            return false;
        }

        String scheme = uri.getScheme();

        if (!"http".equalsIgnoreCase(scheme) && !"https".equalsIgnoreCase(scheme)) {
            return false;
        }

        return !SISFOUR_HOST.equalsIgnoreCase(uri.getHost());
    }

    private static void openExternal(Activity activity, Uri uri) {
        try {
            activity.startActivity(new Intent(Intent.ACTION_VIEW, uri));
        } catch (Exception error) {
            Toast.makeText(
                    activity,
                    "Tidak ada aplikasi untuk membuka tautan ini.",
                    Toast.LENGTH_SHORT
            ).show();
        }
    }

    private static void injectCompatJavaScript(WebView webView) {
        String js =
                "(function(){"
                + "if(window.__sisfourNativeCompatV2)return;"
                + "window.__sisfourNativeCompatV2=true;"

                // External HTTPS links stay outside the bounded SisFour browser.
                + "function externalUrl(u){try{var x=new URL(u,location.href);"
                + "return /^https?:$/.test(x.protocol)&&x.host!==location.host;}catch(e){return false;}}"
                + "document.addEventListener('click',function(e){"
                + "var a=e.target&&e.target.closest?e.target.closest('a[href]'):null;"
                + "if(a&&externalUrl(a.href)){e.preventDefault();SisFourNative.openExternal(a.href);}"
                + "},true);"
                + "var oldOpen=window.open;"
                + "window.open=function(u,t,f){"
                + "if(u&&externalUrl(u)){SisFourNative.openExternal(new URL(u,location.href).href);return null;}"
                + "return oldOpen.call(window,u,t,f);};"

                // Promise registry used by the frontend's existing SisFourFileDownload contract.
                + "var pending=Object.create(null);"
                + "window.__sisfourFileDownloadResolve=function(payload){"
                + "var id=payload&&typeof payload.requestId==='string'?payload.requestId:'';"
                + "var cb=pending[id];if(!cb)return;delete pending[id];"
                + "if(cb.timer)clearTimeout(cb.timer);"
                + "if(payload.ok){cb.resolve(payload.result||{});}"
                + "else{cb.reject(new Error(payload.message||'Download file gagal.'));}"
                + "};"

                + "function internalUrl(value){try{var u=new URL(value,location.href);"
                + "if(u.protocol!=='https:'||u.host!==location.host||u.username||u.password)return null;"
                + "return u.href;}catch(e){return null;}}"

                + "function safeHeaders(raw){var out={};try{var h=new Headers(raw||{});"
                + "h.forEach(function(v,k){var n=String(k).toLowerCase();"
                + "if(n==='accept'||n==='x-requested-with')out[n]=String(v);});"
                + "}catch(e){}return out;}"

                + "function formFields(body){var fields=[];"
                + "if(body instanceof FormData){body.forEach(function(v,k){"
                + "if(typeof v!=='string')throw new TypeError('POST file native hanya menerima field teks.');"
                + "fields.push({name:String(k),value:v});});return fields;}"
                + "if(body instanceof URLSearchParams){body.forEach(function(v,k){"
                + "fields.push({name:String(k),value:String(v)});});return fields;}"
                + "throw new TypeError('Body POST file harus FormData atau URLSearchParams.');}"

                + "window.SisFourFileDownload={post:function(request){"
                + "var url=internalUrl(request&&request.url||'');"
                + "if(!url)return Promise.reject(new Error('URL download tidak diizinkan.'));"
                + "var fields;try{fields=formFields(request&&request.body);}catch(e){return Promise.reject(e);}"
                + "var id='file-'+Date.now().toString(36)+'-'+Math.random().toString(36).slice(2,10);"
                + "return new Promise(function(resolve,reject){"
                + "var timer=setTimeout(function(){if(!pending[id])return;delete pending[id];"
                + "reject(new Error('Download melewati batas waktu.'));},240000);"
                + "pending[id]={resolve:resolve,reject:reject,timer:timer};"
                + "try{SisFourNative.postDownload(JSON.stringify({"
                + "requestId:id,url:url,headers:safeHeaders(request&&request.headers),"
                + "fields:fields,userAgent:navigator.userAgent||''"
                + "}));}catch(e){clearTimeout(timer);delete pending[id];reject(e);}"
                + "});}};"

                // Generic HTML form POST exports use the same native engine.
                + "function looksDownload(p){p=(p||'').toLowerCase();"
                + "return p.indexOf('/export')>=0||p.indexOf('/download')>=0||p.endsWith('/template')||"
                + "p.indexOf('/cetak-massal')>=0||p.indexOf('/export-jpg-zip')>=0||"
                + "p.indexOf('/lampiran/')>=0||p.indexOf('/personalia/file/')>=0;}"
                + "document.addEventListener('submit',function(e){"
                + "var f=e.target;if(!f||String(f.method||'get').toLowerCase()!=='post')return;"
                + "var action;try{action=new URL(f.action||location.href,location.href);}catch(_){return;}"
                + "if(action.origin!==location.origin||!looksDownload(action.pathname))return;"
                + "var fd;try{fd=new FormData(f,e.submitter||undefined);}catch(_){fd=new FormData(f);}"
                + "var hasFile=false;fd.forEach(function(v){if(v instanceof File&&v.size>0)hasFile=true;});"
                + "if(hasFile)return;"
                + "e.preventDefault();"
                + "window.SisFourFileDownload.post({url:action.href,body:fd,headers:{"
                + "'Accept':'*/*','X-Requested-With':'XMLHttpRequest'}})"
                + ".catch(function(err){alert(err&&err.message?err.message:'Download file gagal.');});"
                + "},true);"
                + "})();";

        webView.evaluateJavascript(js, null);
    }

    private static void startDownload(
            Activity activity,
            WebView webView,
            DownloadRequestSpec request
    ) {
        if (!isAllowedDownloadUrl(request.url)) {
            failDownload(
                    activity,
                    webView,
                    request,
                    "Unduhan diblokir: URL bukan SisFour."
            );
            return;
        }

        if (
                Build.VERSION.SDK_INT <= Build.VERSION_CODES.P
                && ContextCompat.checkSelfPermission(
                        activity,
                        Manifest.permission.WRITE_EXTERNAL_STORAGE
                ) != PackageManager.PERMISSION_GRANTED
        ) {
            synchronized (PENDING_DOWNLOADS) {
                DownloadRequestSpec old = PENDING_DOWNLOADS.put(activity, request);

                if (old != null && old.requestId != null) {
                    sendBridgeResult(
                            webView,
                            old.requestId,
                            false,
                            null,
                            null,
                            "Permintaan download sebelumnya digantikan."
                    );
                }
            }

            ActivityCompat.requestPermissions(
                    activity,
                    new String[]{Manifest.permission.WRITE_EXTERNAL_STORAGE},
                    WRITE_STORAGE_REQUEST
            );
            return;
        }

        DOWNLOAD_EXECUTOR.execute(() ->
                performDownload(activity, webView, request)
        );
    }

    private static void resumePendingDownloadIfPossible(
            Activity activity,
            WebView webView
    ) {
        if (Build.VERSION.SDK_INT > Build.VERSION_CODES.P) {
            return;
        }

        if (
                ContextCompat.checkSelfPermission(
                        activity,
                        Manifest.permission.WRITE_EXTERNAL_STORAGE
                ) != PackageManager.PERMISSION_GRANTED
        ) {
            return;
        }

        DownloadRequestSpec pending;

        synchronized (PENDING_DOWNLOADS) {
            pending = PENDING_DOWNLOADS.remove(activity);
        }

        if (pending != null) {
            startDownload(activity, webView, pending);
        }
    }

    private static void performDownload(
            Activity activity,
            WebView webView,
            DownloadRequestSpec request
    ) {
        HttpURLConnection connection = null;

        try {
            connection = (HttpURLConnection) new URL(request.url).openConnection();

            // Redirect to login/session expiry must not silently become a downloaded HTML file.
            connection.setInstanceFollowRedirects(false);
            connection.setConnectTimeout(20000);
            connection.setReadTimeout(180000);
            connection.setRequestMethod(request.method);

            applySafeHeader(connection, "Accept", request.acceptHeader);

            if (
                    !isBlank(request.xRequestedWith)
            ) {
                applySafeHeader(
                        connection,
                        "X-Requested-With",
                        request.xRequestedWith
                );
            }

            String cookies = CookieManager.getInstance().getCookie(request.url);
            if (!isBlank(cookies)) {
                connection.setRequestProperty("Cookie", cookies);
            }

            String userAgent = request.userAgent;
            if (isBlank(userAgent)) {
                userAgent = webView.getSettings().getUserAgentString();
            }

            if (!isBlank(userAgent)) {
                connection.setRequestProperty("User-Agent", userAgent);
            }

            if ("POST".equals(request.method)) {
                byte[] body = request.body == null
                        ? new byte[0]
                        : request.body.getBytes(StandardCharsets.UTF_8);

                if (body.length > MAX_FORM_BODY_BYTES) {
                    throw new IllegalArgumentException("Payload POST file terlalu besar.");
                }

                connection.setDoOutput(true);
                connection.setRequestProperty(
                        "Content-Type",
                        "application/x-www-form-urlencoded; charset=UTF-8"
                );
                connection.setFixedLengthStreamingMode(body.length);

                try (
                        OutputStream output = new BufferedOutputStream(
                                connection.getOutputStream()
                        )
                ) {
                    output.write(body);
                    output.flush();
                }
            }

            int status = connection.getResponseCode();
            syncResponseCookies(connection, request.url);

            if (status >= 300 && status < 400) {
                throw new IllegalStateException(
                        "Download dialihkan oleh server. Sesi mungkin sudah berakhir."
                );
            }

            if (status < 200 || status >= 300) {
                throw new IllegalStateException(
                        "Server menolak download (HTTP " + status + ")."
                );
            }

            String responseDisposition = connection.getHeaderField("Content-Disposition");

            String contentDisposition =
                    !TextUtils.isEmpty(responseDisposition)
                            ? responseDisposition
                            : request.eventContentDisposition;

            String responseMime = normalizeMimeType(connection.getContentType());
            String mimeType = chooseMimeType(responseMime, request.eventMimeType);

            if (
                    TextUtils.isEmpty(contentDisposition)
                    && responseMime.toLowerCase(Locale.ROOT).startsWith("text/html")
            ) {
                throw new IllegalStateException(
                        "Server mengembalikan halaman HTML, bukan file."
                );
            }

            String fileName = extractDownloadFileName(responseDisposition);

            if (TextUtils.isEmpty(fileName)) {
                fileName = extractDownloadFileName(request.eventContentDisposition);
            }

            if (TextUtils.isEmpty(fileName)) {
                fileName = URLUtil.guessFileName(
                        request.url,
                        contentDisposition,
                        mimeType
                );
            }

            fileName = sanitizeFileName(fileName);
            mimeType = resolveMimeType(mimeType, fileName);

            SavedFile saved;

            try (
                    InputStream input = new BufferedInputStream(
                            connection.getInputStream()
                    )
            ) {
                saved = saveStreamToDownloads(
                        activity,
                        input,
                        fileName,
                        mimeType
                );
            }

            if (saved.bytes <= 0) {
                throw new IllegalStateException(
                        "Server mengembalikan file kosong."
                );
            }

            final String finalFileName = saved.fileName;
            final String finalMimeType = saved.mimeType;

            activity.runOnUiThread(() -> {
                Toast.makeText(
                        activity,
                        "File tersimpan: " + finalFileName,
                        Toast.LENGTH_LONG
                ).show();

                if (request.requestId != null) {
                    sendBridgeResult(
                            webView,
                            request.requestId,
                            true,
                            finalFileName,
                            finalMimeType,
                            null
                    );
                }
            });
        } catch (Exception error) {
            failDownload(
                    activity,
                    webView,
                    request,
                    messageFor(error, "Download gagal diproses.")
            );
        } finally {
            if (connection != null) {
                connection.disconnect();
            }
        }
    }

    private static void failDownload(
            Activity activity,
            WebView webView,
            DownloadRequestSpec request,
            String message
    ) {
        activity.runOnUiThread(() -> {
            Toast.makeText(
                    activity,
                    message,
                    Toast.LENGTH_LONG
            ).show();

            if (request.requestId != null) {
                sendBridgeResult(
                        webView,
                        request.requestId,
                        false,
                        null,
                        null,
                        message
                );
            }
        });
    }

    private static void sendBridgeResult(
            WebView webView,
            String requestId,
            boolean ok,
            String fileName,
            String mimeType,
            String message
    ) {
        try {
            JSONObject payload = new JSONObject();
            payload.put("requestId", requestId);
            payload.put("ok", ok);

            if (ok) {
                JSONObject result = new JSONObject();
                result.put("fileName", fileName == null ? "" : fileName);
                result.put("mimeType", mimeType == null ? "" : mimeType);
                payload.put("result", result);
            } else {
                payload.put(
                        "message",
                        message == null ? "Download file gagal." : message
                );
            }

            String script =
                    "if(window.__sisfourFileDownloadResolve){"
                            + "window.__sisfourFileDownloadResolve("
                            + payload
                            + ");"
                            + "}";

            webView.evaluateJavascript(script, null);
        } catch (Exception ignored) {
        }
    }

    private static void syncResponseCookies(
            HttpURLConnection connection,
            String url
    ) {
        try {
            Map<String, List<String>> headers = connection.getHeaderFields();

            if (headers == null || headers.isEmpty()) {
                return;
            }

            CookieManager cookieManager = CookieManager.getInstance();
            boolean changed = false;

            for (Map.Entry<String, List<String>> entry : headers.entrySet()) {
                if (
                        entry.getKey() == null
                        || !"set-cookie".equalsIgnoreCase(entry.getKey())
                        || entry.getValue() == null
                ) {
                    continue;
                }

                for (String value : entry.getValue()) {
                    if (isBlank(value)) {
                        continue;
                    }

                    cookieManager.setCookie(url, value);
                    changed = true;
                }
            }

            if (changed) {
                cookieManager.flush();
            }
        } catch (Exception ignored) {
        }
    }

    private static void applySafeHeader(
            HttpURLConnection connection,
            String name,
            String value
    ) {
        if (value == null) {
            return;
        }

        String safe = value.trim();

        if (
                safe.isEmpty()
                || safe.length() > 1024
                || safe.contains("\r")
                || safe.contains("\n")
        ) {
            return;
        }

        connection.setRequestProperty(name, safe);
    }

    private static String chooseMimeType(
            String responseMimeType,
            String eventMimeType
    ) {
        String response = normalizeMimeType(responseMimeType);
        String event = normalizeMimeType(eventMimeType);

        if (!isGenericMime(response)) {
            return response;
        }

        if (!isGenericMime(event)) {
            return event;
        }

        return response;
    }

    private static boolean isGenericMime(String value) {
        if (isBlank(value)) {
            return true;
        }

        String mime = value.trim().toLowerCase(Locale.ROOT);

        return "application/octet-stream".equals(mime)
                || "binary/octet-stream".equals(mime)
                || "application/download".equals(mime)
                || "application/force-download".equals(mime);
    }

    private static String resolveMimeType(
            String mimeType,
            String fileName
    ) {
        String normalized = normalizeMimeType(mimeType);
        String extension = extensionOf(fileName);

        // SQL is explicitly part of SisFour backup/export capability.
        if ("sql".equals(extension)) {
            return "application/sql";
        }

        if (!isGenericMime(normalized)) {
            return normalized;
        }

        if (!extension.isEmpty()) {
            String inferred = MimeTypeMap
                    .getSingleton()
                    .getMimeTypeFromExtension(extension);

            if (!TextUtils.isEmpty(inferred)) {
                return inferred;
            }

            String canonical = canonicalMimeForExtension(extension);

            if (!TextUtils.isEmpty(canonical)) {
                return canonical;
            }
        }

        return TextUtils.isEmpty(normalized)
                ? "application/octet-stream"
                : normalized;
    }

    private static String canonicalMimeForExtension(String extension) {
        if (extension == null) {
            return "";
        }

        switch (extension.toLowerCase(Locale.ROOT)) {
            case "xlsx":
                return "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet";
            case "xls":
                return "application/vnd.ms-excel";
            case "zip":
                return "application/zip";
            case "pdf":
                return "application/pdf";
            case "png":
                return "image/png";
            case "jpg":
            case "jpeg":
                return "image/jpeg";
            case "sql":
                return "application/sql";
            case "csv":
                return "text/csv";
            case "txt":
                return "text/plain";
            case "json":
                return "application/json";
            case "doc":
                return "application/msword";
            case "docx":
                return "application/vnd.openxmlformats-officedocument.wordprocessingml.document";
            case "ods":
                return "application/vnd.oasis.opendocument.spreadsheet";
            default:
                return "";
        }
    }

    private static boolean isBlank(String value) {
        return value == null || value.trim().isEmpty();
    }

    private static String normalizeMimeType(String value) {
        if (value == null) {
            return "";
        }

        int separator = value.indexOf(';');

        return (
                separator >= 0
                        ? value.substring(0, separator)
                        : value
        ).trim();
    }

    private static String extensionOf(String fileName) {
        if (fileName == null) {
            return "";
        }

        int dot = fileName.lastIndexOf('.');

        if (dot < 0 || dot >= fileName.length() - 1) {
            return "";
        }

        return fileName
                .substring(dot + 1)
                .trim()
                .toLowerCase(Locale.ROOT);
    }

    private static String extractDownloadFileName(
            String contentDisposition
    ) {
        if (TextUtils.isEmpty(contentDisposition)) {
            return "";
        }

        String fallback = "";

        for (String rawPart : contentDisposition.split(";")) {
            String part = rawPart == null ? "" : rawPart.trim();
            int separator = part.indexOf('=');

            if (separator <= 0) {
                continue;
            }

            String key = part
                    .substring(0, separator)
                    .trim()
                    .toLowerCase(Locale.ROOT);

            String value = part
                    .substring(separator + 1)
                    .trim();

            if (
                    value.length() >= 2
                    && (
                            (value.startsWith("\"") && value.endsWith("\""))
                            || (value.startsWith("'") && value.endsWith("'"))
                    )
            ) {
                value = value.substring(1, value.length() - 1);
            }

            if ("filename*".equals(key)) {
                int charsetSeparator = value.indexOf("''");

                String encoded =
                        charsetSeparator >= 0
                                ? value.substring(charsetSeparator + 2)
                                : value;

                try {
                    String decoded = URLDecoder.decode(
                            encoded,
                            StandardCharsets.UTF_8.name()
                    );

                    if (!TextUtils.isEmpty(decoded)) {
                        return decoded;
                    }
                } catch (Exception ignored) {
                }
            }

            if ("filename".equals(key) && !TextUtils.isEmpty(value)) {
                fallback = value.replace("\\\"", "\"");
            }
        }

        return fallback;
    }

    private static String sanitizeFileName(String value) {
        String safe = value == null ? "" : value.trim();

        safe = safe
                .replace('\\', '_')
                .replace('/', '_')
                .replace(':', '_')
                .replace('*', '_')
                .replace('?', '_')
                .replace('"', '_')
                .replace('<', '_')
                .replace('>', '_')
                .replace('|', '_')
                .replaceAll("[\\x00-\\x1F\\x7F]", "_")
                .trim();

        while (safe.endsWith(".") || safe.endsWith(" ")) {
            safe = safe.substring(0, safe.length() - 1);
        }

        if (safe.isEmpty()) {
            safe = "download.bin";
        }

        if (safe.length() > 180) {
            String extension = extensionOf(safe);

            if (!extension.isEmpty()) {
                String suffix = "." + extension;
                int keep = Math.max(1, 180 - suffix.length());
                safe = safe.substring(0, keep) + suffix;
            } else {
                safe = safe.substring(0, 180);
            }
        }

        return safe;
    }

    private static SavedFile saveStreamToDownloads(
            Activity activity,
            InputStream input,
            String fileName,
            String mimeType
    ) throws Exception {
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.Q) {
            ContentValues values = new ContentValues();

            values.put(MediaStore.Downloads.DISPLAY_NAME, fileName);
            values.put(
                    MediaStore.Downloads.MIME_TYPE,
                    TextUtils.isEmpty(mimeType)
                            ? "application/octet-stream"
                            : mimeType
            );
            values.put(
                    MediaStore.Downloads.RELATIVE_PATH,
                    Environment.DIRECTORY_DOWNLOADS + "/SisFour"
            );
            values.put(MediaStore.Downloads.IS_PENDING, 1);

            Uri item = activity.getContentResolver().insert(
                    MediaStore.Downloads.EXTERNAL_CONTENT_URI,
                    values
            );

            if (item == null) {
                throw new IllegalStateException(
                        "Tidak dapat membuat file download."
                );
            }

            long bytes;

            try {
                try (
                        OutputStream rawOutput =
                                activity.getContentResolver().openOutputStream(item);
                        OutputStream output =
                                rawOutput == null
                                        ? null
                                        : new BufferedOutputStream(rawOutput)
                ) {
                    if (output == null) {
                        throw new IllegalStateException(
                                "Tidak dapat membuka file download."
                        );
                    }

                    bytes = copy(input, output);
                }

                if (bytes <= 0) {
                    activity.getContentResolver().delete(item, null, null);
                    throw new IllegalStateException(
                            "Server mengembalikan file kosong."
                    );
                }

                ContentValues ready = new ContentValues();
                ready.put(MediaStore.Downloads.IS_PENDING, 0);
                activity.getContentResolver().update(item, ready, null, null);

                return new SavedFile(fileName, mimeType, bytes);
            } catch (Exception error) {
                try {
                    activity.getContentResolver().delete(item, null, null);
                } catch (Exception ignored) {
                }

                throw error;
            }
        }

        File base = new File(
                Environment.getExternalStoragePublicDirectory(
                        Environment.DIRECTORY_DOWNLOADS
                ),
                "SisFour"
        );

        if (!base.exists() && !base.mkdirs()) {
            throw new IllegalStateException(
                    "Tidak dapat membuat folder Downloads/SisFour."
            );
        }

        File outputFile = uniqueFile(base, fileName);
        long bytes;

        try (
                OutputStream output = new BufferedOutputStream(
                        new FileOutputStream(outputFile)
                )
        ) {
            bytes = copy(input, output);
        }

        if (bytes <= 0) {
            //noinspection ResultOfMethodCallIgnored
            outputFile.delete();

            throw new IllegalStateException(
                    "Server mengembalikan file kosong."
            );
        }

        MediaScannerConnection.scanFile(
                activity,
                new String[]{outputFile.getAbsolutePath()},
                new String[]{mimeType},
                null
        );

        return new SavedFile(
                outputFile.getName(),
                mimeType,
                bytes
        );
    }

    private static File uniqueFile(File directory, String fileName) {
        File candidate = new File(directory, fileName);

        if (!candidate.exists()) {
            return candidate;
        }

        String base = fileName;
        String extension = "";

        int dot = fileName.lastIndexOf('.');

        if (dot > 0) {
            base = fileName.substring(0, dot);
            extension = fileName.substring(dot);
        }

        int index = 1;

        while (candidate.exists()) {
            candidate = new File(
                    directory,
                    base + " (" + index + ")" + extension
            );
            index++;
        }

        return candidate;
    }

    private static long copy(
            InputStream input,
            OutputStream output
    ) throws Exception {
        byte[] buffer = new byte[64 * 1024];
        long total = 0L;
        int read;

        while ((read = input.read(buffer)) != -1) {
            output.write(buffer, 0, read);
            total += read;
        }

        output.flush();

        return total;
    }

    private static String buildFormBody(JSONArray fields) throws Exception {
        if (fields == null) {
            return "";
        }

        if (fields.length() > MAX_FORM_FIELDS) {
            throw new IllegalArgumentException(
                    "Jumlah field POST terlalu banyak."
            );
        }

        StringBuilder body = new StringBuilder();

        for (int index = 0; index < fields.length(); index++) {
            JSONObject field = fields.optJSONObject(index);

            if (field == null) {
                throw new IllegalArgumentException(
                        "Field POST tidak valid."
                );
            }

            String name = field.optString("name", "");
            String value = field.optString("value", "");

            if (
                    name.isEmpty()
                    || name.length() > 256
                    || value.length() > 12 * 1024 * 1024
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
                            StandardCharsets.UTF_8.name()
                    )
            );

            body.append('=');

            body.append(
                    URLEncoder.encode(
                            value,
                            StandardCharsets.UTF_8.name()
                    )
            );

            if (body.length() > MAX_FORM_BODY_BYTES) {
                throw new IllegalArgumentException(
                        "Payload POST file terlalu besar."
                );
            }
        }

        return body.toString();
    }

    private static String messageFor(
            Exception error,
            String fallback
    ) {
        String message = error.getMessage();

        if (message == null || message.trim().isEmpty()) {
            return fallback;
        }

        return message.trim();
    }

    public static final class BrowserBridge {
        private final Activity activity;
        private final WebView webView;

        BrowserBridge(Activity activity, WebView webView) {
            this.activity = activity;
            this.webView = webView;
        }

        @JavascriptInterface
        public void openExternal(String url) {
            activity.runOnUiThread(() -> {
                try {
                    Uri uri = Uri.parse(url);

                    if (isExternalHttpUrl(uri)) {
                        SisFourApplication.openExternal(
                                activity,
                                uri
                        );
                    }
                } catch (Exception ignored) {
                }
            });
        }

        @JavascriptInterface
        public void postDownload(String requestJson) {
            String requestId = "";

            try {
                JSONObject request = new JSONObject(
                        requestJson == null ? "{}" : requestJson
                );

                requestId = request.optString(
                        "requestId",
                        ""
                );

                String url = request.optString(
                        "url",
                        ""
                );

                if (requestId.isEmpty()) {
                    throw new IllegalArgumentException(
                            "Request ID download tidak valid."
                    );
                }

                if (!isAllowedDownloadUrl(url)) {
                    throw new IllegalArgumentException(
                            "URL download tidak diizinkan."
                    );
                }

                JSONObject headers = request.optJSONObject(
                        "headers"
                );

                String accept = headers == null
                        ? "*/*"
                        : headers.optString(
                                "accept",
                                headers.optString(
                                        "Accept",
                                        "*/*"
                                )
                        );

                String xRequestedWith = headers == null
                        ? ""
                        : headers.optString(
                                "x-requested-with",
                                headers.optString(
                                        "X-Requested-With",
                                        ""
                                )
                        );

                String body = buildFormBody(
                        request.optJSONArray("fields")
                );

                String userAgent = request.optString(
                        "userAgent",
                        ""
                );

                DownloadRequestSpec spec = DownloadRequestSpec.post(
                        requestId,
                        url,
                        body,
                        accept,
                        xRequestedWith,
                        userAgent
                );

                startDownload(
                        activity,
                        webView,
                        spec
                );
            } catch (Exception error) {
                final String id = requestId;
                final String message = messageFor(
                        error,
                        "POST download gagal diproses."
                );

                activity.runOnUiThread(() -> {
                    Toast.makeText(
                            activity,
                            message,
                            Toast.LENGTH_LONG
                    ).show();

                    if (!id.isEmpty()) {
                        sendBridgeResult(
                                webView,
                                id,
                                false,
                                null,
                                null,
                                message
                        );
                    }
                });
            }
        }
    }

    private static final class DownloadRequestSpec {
        final String requestId;
        final String method;
        final String url;
        final String body;
        final String acceptHeader;
        final String xRequestedWith;
        final String eventMimeType;
        final String eventContentDisposition;
        final String userAgent;

        private DownloadRequestSpec(
                String requestId,
                String method,
                String url,
                String body,
                String acceptHeader,
                String xRequestedWith,
                String eventMimeType,
                String eventContentDisposition,
                String userAgent
        ) {
            this.requestId = requestId;
            this.method = method;
            this.url = url;
            this.body = body;
            this.acceptHeader =
                    isBlank(acceptHeader)
                            ? "*/*"
                            : acceptHeader;
            this.xRequestedWith =
                    xRequestedWith == null
                            ? ""
                            : xRequestedWith;
            this.eventMimeType =
                    eventMimeType == null
                            ? ""
                            : eventMimeType;
            this.eventContentDisposition =
                    eventContentDisposition == null
                            ? ""
                            : eventContentDisposition;
            this.userAgent =
                    userAgent == null
                            ? ""
                            : userAgent;
        }

        static DownloadRequestSpec get(
                String url,
                String eventMimeType,
                String eventContentDisposition,
                String userAgent
        ) {
            return new DownloadRequestSpec(
                    null,
                    "GET",
                    url,
                    null,
                    "*/*",
                    "",
                    eventMimeType,
                    eventContentDisposition,
                    userAgent
            );
        }

        static DownloadRequestSpec post(
                String requestId,
                String url,
                String body,
                String acceptHeader,
                String xRequestedWith,
                String userAgent
        ) {
            return new DownloadRequestSpec(
                    requestId,
                    "POST",
                    url,
                    body,
                    acceptHeader,
                    xRequestedWith,
                    "",
                    "",
                    userAgent
            );
        }
    }

    private static final class SavedFile {
        final String fileName;
        final String mimeType;
        final long bytes;

        SavedFile(
                String fileName,
                String mimeType,
                long bytes
        ) {
            this.fileName = fileName;
            this.mimeType =
                    isBlank(mimeType)
                            ? "application/octet-stream"
                            : mimeType;
            this.bytes = bytes;
        }
    }

    @Override public void onActivityCreated(Activity activity, Bundle savedInstanceState) {}
    @Override public void onActivityStarted(Activity activity) {}
    @Override public void onActivityPaused(Activity activity) {}
    @Override public void onActivityStopped(Activity activity) {}
    @Override public void onActivitySaveInstanceState(Activity activity, Bundle outState) {}
    @Override public void onActivityDestroyed(Activity activity) {}
}
