import * as CookieConsent from 'vanilla-cookieconsent';

window.CookieConsent = CookieConsent;

// The separate GUI-less "core" build (vanilla-cookieconsent/dist/core/...) throws
// on init (upstream bug - a required internal field is never assigned in that
// build). Using the full build instead, but never calling its UI methods
// (show/showPreferences): with autoShow disabled and lazyHtmlGeneration on by
// default, its own modal DOM is only ever created on demand - since we never ask
// for it, nothing gets rendered. Only cookie/consent-state logic is actually
// used; the visible banner below is our own markup/CSS driven through the
// public API - see resources/js/README.md.
CookieConsent.run({
    mode: 'opt-in',
    autoShow: false,
    // Bump this whenever the policy/categories change meaningfully - anyone
    // who already consented under a lower revision gets re-prompted
    // automatically instead of silently keeping stale consent.
    revision: 0,
    categories: {
        necessary: {
            readOnly: true,
            enabled: true,
        },
        analytics: {
            enabled: false,
        },
        marketing: {
            enabled: false,
        },
    },
    language: {
        default: 'de',
        translations: {
            de: {
                consentModal: {
                    title: 'Cookie-Einstellungen',
                    description: 'Wir verwenden Cookies, um unsere Website zu betreiben. Optionale Cookies setzen wir nur mit Ihrer Zustimmung.',
                    acceptAllBtn: 'Alle akzeptieren',
                    acceptNecessaryBtn: 'Nur notwendige',
                    showPreferencesBtn: 'Einstellungen',
                },
                preferencesModal: {
                    title: 'Cookie-Einstellungen',
                    acceptAllBtn: 'Alle akzeptieren',
                    acceptNecessaryBtn: 'Nur notwendige',
                    savePreferencesBtn: 'Auswahl speichern',
                    sections: [
                        {
                            title: 'Notwendig',
                            description: 'Erforderlich, damit die Website grundlegend funktioniert.',
                            linkedCategory: 'necessary',
                        },
                        {
                            title: 'Analyse',
                            description: 'Hilft uns zu verstehen, wie die Website genutzt wird.',
                            linkedCategory: 'analytics',
                        },
                        {
                            title: 'Marketing',
                            description: 'Für personalisierte Werbung und Kampagnenmessung.',
                            linkedCategory: 'marketing',
                        },
                    ],
                },
            },
        },
    },
}).then(() => {
    initBanner();
});

function initBanner() {
    const banner = document.getElementById('consent-banner');

    if (!banner) {
        return;
    }

    const acceptAllBtn = banner.querySelector('[data-consent-accept-all]');
    const acceptNecessaryBtn = banner.querySelector('[data-consent-accept-necessary]');
    const togglePrefsBtn = banner.querySelector('[data-consent-toggle-preferences]');
    const savePrefsBtn = banner.querySelector('[data-consent-save-preferences]');
    const prefsPanel = banner.querySelector('[data-consent-preferences]');
    const analyticsToggle = banner.querySelector('[data-consent-category="analytics"]');
    const marketingToggle = banner.querySelector('[data-consent-category="marketing"]');

    function hideBanner() {
        banner.classList.remove('consent-banner--visible');
    }

    function showBanner() {
        banner.classList.add('consent-banner--visible');
    }

    acceptAllBtn.addEventListener('click', () => {
        CookieConsent.acceptCategory('all');
        hideBanner();
    });

    acceptNecessaryBtn.addEventListener('click', () => {
        CookieConsent.acceptCategory([]);
        hideBanner();
    });

    togglePrefsBtn.addEventListener('click', () => {
        prefsPanel.classList.toggle('consent-banner__preferences--open');
    });

    savePrefsBtn.addEventListener('click', () => {
        const selected = [];
        if (analyticsToggle.checked) {
            selected.push('analytics');
        }
        if (marketingToggle.checked) {
            selected.push('marketing');
        }
        CookieConsent.acceptCategory(selected);
        hideBanner();
    });

    if (!CookieConsent.validConsent()) {
        showBanner();
    }
}
