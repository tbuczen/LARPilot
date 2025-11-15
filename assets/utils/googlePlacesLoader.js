/**
 * Loads the Google Places API library dynamically
 * @param {string} apiKey - Google Maps API key with Places API enabled
 * @returns {Promise<void>}
 */
export function loadGooglePlacesApi(apiKey) {
    return new Promise((resolve, reject) => {
        // Check if already loaded
        if (window.google && window.google.maps && window.google.maps.places) {
            resolve();
            return;
        }

        // Define a unique callback name
        const callbackName = 'initGooglePlaces_' + Date.now();

        // Set up the callback
        window[callbackName] = () => {
            // Clean up the callback
            delete window[callbackName];

            // Verify the API loaded correctly
            if (window.google && window.google.maps && window.google.maps.places) {
                resolve();
            } else {
                reject(new Error('Google Places API failed to initialize'));
            }
        };

        // Create script element with callback
        const script = document.createElement('script');
        script.src = `https://maps.googleapis.com/maps/api/js?key=${apiKey}&libraries=places&callback=${callbackName}`;
        script.async = true;
        script.defer = true;

        script.onerror = () => {
            delete window[callbackName];
            reject(new Error('Failed to load Google Places API script'));
        };

        document.head.appendChild(script);
    });
}
