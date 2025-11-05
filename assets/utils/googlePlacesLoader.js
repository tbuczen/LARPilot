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

        // Create script element
        const script = document.createElement('script');
        script.src = `https://maps.googleapis.com/maps/api/js?key=${apiKey}&libraries=places&loading=async`;
        script.async = true;
        script.defer = true;

        script.onload = () => {
            if (window.google && window.google.maps && window.google.maps.places) {
                resolve();
            } else {
                reject(new Error('Google Places API failed to initialize'));
            }
        };

        script.onerror = () => {
            reject(new Error('Failed to load Google Places API script'));
        };

        document.head.appendChild(script);
    });
}
