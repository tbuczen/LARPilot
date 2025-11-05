import { Controller } from '@hotwired/stimulus';
import { loadGooglePlacesApi } from '../utils/googlePlacesLoader.js';

/**
 * Google Places Autocomplete Controller
 *
 * Provides autocomplete for location address fields with automatic
 * population of country, city, postal code, and coordinates.
 *
 * Usage:
 * <div data-controller="google-places-autocomplete"
 *      data-google-places-autocomplete-api-key-value="YOUR_API_KEY"
 *      data-google-places-autocomplete-address-field-value="#location_address"
 *      data-google-places-autocomplete-city-field-value="#location_city"
 *      data-google-places-autocomplete-country-field-value="#location_country"
 *      data-google-places-autocomplete-postal-code-field-value="#location_postalCode"
 *      data-google-places-autocomplete-latitude-field-value="#location_latitude"
 *      data-google-places-autocomplete-longitude-field-value="#location_longitude">
 * </div>
 */
export default class extends Controller {
    static values = {
        apiKey: String,
        addressField: String,
        cityField: String,
        countryField: String,
        postalCodeField: String,
        latitudeField: String,
        longitudeField: String
    };

    async connect() {
        if (!this.apiKeyValue) {
            console.warn('Google Places API key not provided');
            return;
        }

        try {
            // Load Google Places API
            await loadGooglePlacesApi(this.apiKeyValue);
            this.initAutocomplete();
        } catch (error) {
            console.error('Failed to load Google Places API:', error);
        }
    }

    disconnect() {
        // Cleanup if needed
        if (this.autocomplete) {
            google.maps.event.clearInstanceListeners(this.autocomplete);
        }
    }

    initAutocomplete() {
        const addressInput = document.querySelector(this.addressFieldValue);

        if (!addressInput) {
            console.warn('Address field not found:', this.addressFieldValue);
            return;
        }

        // Create autocomplete instance
        this.autocomplete = new google.maps.places.Autocomplete(addressInput, {
            types: ['establishment', 'geocode'], // Allow both places and addresses
            fields: ['address_components', 'geometry', 'name', 'formatted_address']
        });

        // Listen for place selection
        this.autocomplete.addListener('place_changed', () => {
            this.handlePlaceSelect();
        });
    }

    handlePlaceSelect() {
        const place = this.autocomplete.getPlace();

        if (!place.address_components) {
            console.warn('No address components found for selected place');
            return;
        }

        // Extract address components
        const components = this.extractAddressComponents(place.address_components);

        // Fill form fields
        this.fillField(this.addressFieldValue, place.formatted_address || place.name);
        this.fillField(this.cityFieldValue, components.city);
        this.fillField(this.countryFieldValue, components.country);
        this.fillField(this.postalCodeFieldValue, components.postalCode);

        // Fill coordinates if available
        if (place.geometry && place.geometry.location) {
            this.fillField(this.latitudeFieldValue, place.geometry.location.lat().toFixed(8));
            this.fillField(this.longitudeFieldValue, place.geometry.location.lng().toFixed(8));
        }
    }

    extractAddressComponents(components) {
        const result = {
            street: '',
            city: '',
            country: '',
            postalCode: ''
        };

        components.forEach(component => {
            const types = component.types;

            // Street number
            if (types.includes('street_number')) {
                result.street = component.long_name + ' ';
            }

            // Street name
            if (types.includes('route')) {
                result.street += component.long_name;
            }

            // City - try multiple types in order of preference
            if (types.includes('locality')) {
                result.city = component.long_name;
            } else if (!result.city && types.includes('postal_town')) {
                result.city = component.long_name;
            } else if (!result.city && types.includes('administrative_area_level_2')) {
                result.city = component.long_name;
            }

            // Country
            if (types.includes('country')) {
                result.country = component.long_name;
            }

            // Postal code
            if (types.includes('postal_code')) {
                result.postalCode = component.long_name;
            }
        });

        return result;
    }

    fillField(selector, value) {
        if (!selector || !value) {
            return;
        }

        const field = document.querySelector(selector);
        if (field) {
            field.value = value;
            // Trigger change event for any listeners
            field.dispatchEvent(new Event('change', { bubbles: true }));
        }
    }
}
