(() => {
    const STORAGE_KEY = 'cdcocktails_install_banner_hidden_until';
    const todayKey = () => new Date().toISOString().slice(0, 10);

    const DEV_MODE = window.__DEV_MODE__ === true || window.__DEV_MODE__ === 'true';

    const isStandalone = () =>
        window.matchMedia('(display-mode: standalone)').matches ||
        window.navigator.standalone === true;

    const isIOS = () => {
        const ua = navigator.userAgent || '';
        const iOS = /iPad|iPhone|iPod/.test(ua);
        const isMacTouch = /Macintosh/.test(ua) && navigator.maxTouchPoints > 1;
        return iOS || isMacTouch;
    };

    const isAndroid = () => /Android/i.test(navigator.userAgent || '');

    const hiddenToday = () => localStorage.getItem(STORAGE_KEY) === todayKey();
    const hideForToday = () => localStorage.setItem(STORAGE_KEY, todayKey());

    // Don’t show if installed
    if (isStandalone()) return;

    // Don’t show if dismissed today (except DEV)
    if (!DEV_MODE && hiddenToday()) return;

    const banner = document.createElement('div');
    banner.className = 'install-banner';
    banner.innerHTML = `
    <button class="install-close" aria-label="Schließen">×</button>
    <button class="install-cta" type="button">App installieren</button>
  `;
    document.body.appendChild(banner);

    const closeBtn = banner.querySelector('.install-close');
    const ctaBtn = banner.querySelector('.install-cta');

    function removeBannerOnly() {
        banner.remove();
    }

    closeBtn.addEventListener('click', () => {
        removeBannerOnly();
        if (!DEV_MODE) hideForToday(); // nur in Prod merken
    });

    // Android native prompt hook
    let deferredPrompt = null;
    window.addEventListener('beforeinstallprompt', (e) => {
        e.preventDefault();
        deferredPrompt = e;
    });

    function showInstallHelpModal() {
        const overlay = document.createElement('div');
        overlay.className = 'install-modal-overlay';

        const title = isIOS()
            ? 'Installation auf deinem iPhone'
            : (isAndroid() ? 'Installation auf Android' : 'Installation');

        const shareIcon = `
      <svg class="install-ico" viewBox="0 0 24 24" aria-hidden="true">
        <path fill="currentColor" d="M12 3l4 4h-3v7h-2V7H8l4-4zm-7 9h2v7h10v-7h2v9H5v-9z"/>
      </svg>
    `;

        const addHomeIcon = `
      <svg class="install-ico" viewBox="0 0 24 24" aria-hidden="true">
        <path fill="currentColor" d="M7 3h10a2 2 0 012 2v14a2 2 0 01-2 2H7a2 2 0 01-2-2V5a2 2 0 012-2zm0 2v14h10V5H7zm5 3a1 1 0 011 1v2h2a1 1 0 110 2h-2v2a1 1 0 11-2 0v-2H9a1 1 0 110-2h2V9a1 1 0 011-1z"/>
      </svg>
    `;

        const steps = isIOS()
            ? `
        <ol class="install-steps">
          <li>
            Tippe auf <strong>Teilen</strong>
            <span class="install-ico-wrap">${shareIcon}</span>
          </li>
          <li>
            Wähle <strong>Zum Home-Bildschirm</strong>
            <span class="install-ico-wrap">${addHomeIcon}</span>
          </li>
          <li>Tippe <strong>Hinzufügen</strong>.</li>
        </ol>
      `
            : (isAndroid()
                ? `
          <ol class="install-steps">
            <li>Tippe im Browser-Menü auf <strong>Installieren</strong> oder <strong>Zum Startbildschirm hinzufügen</strong>.</li>
            <li>Bestätige mit <strong>Installieren</strong>.</li>
          </ol>
        `
                : `<p>Nutze im Browser-Menü die Funktion <strong>Zum Startbildschirm hinzufügen</strong>.</p>`);

        overlay.innerHTML = `
      <div class="install-modal" role="dialog" aria-modal="true" aria-label="${title}">
        <div class="install-modal-header">
          <div class="install-modal-title">${title}</div>
          <button class="install-modal-close" aria-label="Schließen">×</button>
        </div>
        <div class="install-modal-body">
          ${steps}
        </div>
        <div class="install-modal-footer">
          <button class="install-modal-ok" type="button">Okay</button>
        </div>
      </div>
    `;

        document.body.appendChild(overlay);

        const kill = () => overlay.remove();
        overlay.querySelector('.install-modal-close').addEventListener('click', kill);
        overlay.querySelector('.install-modal-ok').addEventListener('click', kill);
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) kill();
        });
    }

    ctaBtn.addEventListener('click', async () => {
        // CTA: Banner nur visuell weg, aber NICHT für heute merken
        // damit es beim Reload wieder erscheint, falls nicht installiert wurde
        removeBannerOnly();

        // Android: native prompt wenn vorhanden
        if (deferredPrompt) {
            deferredPrompt.prompt();
            let outcome = null;
            try {
                const choice = await deferredPrompt.userChoice;
                outcome = choice?.outcome || null; // 'accepted' | 'dismissed'
            } catch {}
            deferredPrompt = null;

            // Wenn wirklich installiert/accepted: optional "für heute" merken (eigentlich egal, weil dann standalone)
            if (outcome === 'accepted' && !DEV_MODE) hideForToday();
            return;
        }

        // iOS: Anleitung zeigen (kein nativer prompt möglich)
        showInstallHelpModal();
        // NICHT hideForToday: wenn Nutzer es nicht macht, soll es bei Reload wieder kommen
    });

    // iOS: wenn Nutzer zwischenzeitlich installiert, banner wäre ohnehin weg wegen isStandalone beim nächsten load
})();