export function loadGoogleApi() {
    return new Promise((resolve, reject) => {
        if (window.gapi) {
            resolve(window.gapi);
            return;
        }

        const script = document.createElement("script");
        script.src = "https://apis.google.com/js/api.js";
        script.async = true;
        script.onload = () => {
            gapi.load("picker", { callback: () => resolve(gapi) });
        };
        script.onerror = () => reject(new Error("❌ Failed to load Google API"));
        document.head.appendChild(script);
    });
}
