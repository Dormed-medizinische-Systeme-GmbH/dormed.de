/**
 * Client-side half of the form spam protection (App\Support\FormGuard).
 * Patterns, whitelist and messages come from PHP via the form's
 * data-form-guard attribute, so frontend and backend never drift apart -
 * the backend still enforces everything on its own.
 *
 * For every <form data-form-guard="{...}">:
 *  - script fields (message): characters outside the allowed scripts
 *    (Cyrillic, Georgian, ...) are blocked while typing and stripped from
 *    pastes/drops, with a short hint below the field
 *  - all guarded fields: invisible characters (zero-width space etc.) are
 *    stripped, foreign links mark the field as invalid while typing
 *
 * window.dormedFormGuard.problems(form) returns the same messages the
 * backend would give, for the forms' own submit validation.
 */

const HINT_DURATION_MS = 6000;

function configOf(form) {
    try {
        return JSON.parse(form.dataset.formGuard || '{}');
    } catch {
        return {};
    }
}

function disallowedCharacters(config, value) {
    if (!config.disallowedScriptPattern || !value) {
        return [];
    }

    return [...new Set(value.match(new RegExp(config.disallowedScriptPattern, 'gu')) || [])];
}

function scriptMessage(config, characters) {
    const examples = characters.slice(0, 3).map((character) => `„${character}“`).join(' ');

    return `${config.scriptMessageBase}${examples ? ` (gefunden: ${examples})` : ''}.`;
}

function hasForeignLink(config, value) {
    if (!config.linkPattern || !value) {
        return false;
    }

    const links = value.match(new RegExp(config.linkPattern, 'gi')) || [];

    return links.some((link) => {
        const host = link
            .replace(/^https?:\/\//i, '')
            .split(/[/?#:]/)[0]
            .toLowerCase()
            .replace(/[.,;!?)]+$/, '');

        return !(config.allowedDomains || []).some((domain) => {
            const allowed = domain.toLowerCase();

            return host === allowed || host.endsWith(`.${allowed}`);
        });
    });
}

function clean(config, field, value) {
    let cleaned = value.replace(new RegExp(config.invisiblePattern, 'gu'), '');

    if ((config.scriptFields || []).includes(field.name) && config.disallowedScriptPattern) {
        cleaned = cleaned.replace(new RegExp(config.disallowedScriptPattern, 'gu'), '');
    }

    return cleaned;
}

function showHint(field, message) {
    let hint = field.parentNode.querySelector('.form-guard__hint');

    if (!hint) {
        hint = document.createElement('p');
        hint.className = 'form-guard__hint';
        hint.setAttribute('role', 'status');
        hint.style.cssText = 'margin:0.35rem 0 0;font-size:0.8rem;line-height:1.4;color:rgb(185,28,28);';
        field.insertAdjacentElement('afterend', hint);
    }

    hint.textContent = message;
    hint.hidden = false;
    clearTimeout(hint.hideTimer);
    hint.hideTimer = setTimeout(() => {
        hint.hidden = true;
    }, HINT_DURATION_MS);
}

/**
 * Removes invisible/disallowed characters from the field while keeping
 * the caret where the user left it.
 */
function sanitizeField(config, field) {
    const value = field.value;
    const cleaned = clean(config, field, value);

    if (cleaned === value) {
        return;
    }

    const caret = field.selectionStart ?? value.length;
    const cleanedBeforeCaret = clean(config, field, value.slice(0, caret));
    const removed = disallowedCharacters(config, value);

    field.value = cleaned;
    field.setSelectionRange(cleanedBeforeCaret.length, cleanedBeforeCaret.length);

    if (removed.length > 0) {
        showHint(field, scriptMessage(config, removed));
    }
}

function markLinks(config, field) {
    if (hasForeignLink(config, field.value)) {
        field.setAttribute('aria-invalid', 'true');
    } else if (field.value.trim()) {
        field.removeAttribute('aria-invalid');
    }
}

function attach(form) {
    const config = configOf(form);
    const guarded = [...new Set([...(config.linkFields || []), ...(config.scriptFields || [])])];
    const isGuarded = (target) => target && guarded.includes(target.name);

    // Typing: refuse disallowed characters before they ever reach the field.
    form.addEventListener('beforeinput', (event) => {
        const field = event.target;

        if (!isGuarded(field) || event.isComposing || typeof event.data !== 'string') {
            return;
        }

        const cleaned = clean(config, field, event.data);

        if (cleaned === event.data) {
            return;
        }

        event.preventDefault();

        if (cleaned) {
            field.setRangeText(cleaned, field.selectionStart, field.selectionEnd, 'end');
            field.dispatchEvent(new Event('input', { bubbles: true }));
        }

        const removed = disallowedCharacters(config, event.data);

        if (removed.length > 0) {
            showHint(field, scriptMessage(config, removed));
        }
    });

    // Paste, drop, autofill and anything beforeinput could not see.
    form.addEventListener('input', (event) => {
        const field = event.target;

        if (!isGuarded(field) || event.isComposing) {
            return;
        }

        sanitizeField(config, field);

        if ((config.linkFields || []).includes(field.name)) {
            markLinks(config, field);
        }
    });

    form.addEventListener('compositionend', (event) => {
        if (isGuarded(event.target)) {
            sanitizeField(config, event.target);
        }
    });
}

/**
 * @returns {{ field: HTMLElement, message: string }[]}
 */
function problems(form) {
    const config = configOf(form);
    const found = [];

    (config.linkFields || []).forEach((name) => {
        const field = form.querySelector(`[name="${name}"]`);

        if (field && hasForeignLink(config, field.value)) {
            found.push({ field, message: config.linkMessage });
        }
    });

    (config.scriptFields || []).forEach((name) => {
        const field = form.querySelector(`[name="${name}"]`);
        const characters = field ? disallowedCharacters(config, field.value) : [];

        if (characters.length > 0) {
            found.push({ field, message: scriptMessage(config, characters) });
        }
    });

    return found;
}

window.dormedFormGuard = { problems };

document.querySelectorAll('form[data-form-guard]').forEach(attach);
