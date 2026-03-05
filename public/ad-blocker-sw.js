/**
 * Hustel Stream Party — Universal Ad Blocker Service Worker
 *
 * ✅ Blocks ads from ALL sites on this origin (not just YouTube).
 * ✅ Covers: video ad networks, display ads, tracking pixels, analytics beacons,
 *    social trackers, affiliate/redirect networks, and data-brokers.
 * ✅ Works for every user who opens the site — no browser extension needed.
 *
 * HOW IT WORKS:
 *   Every network request from any page served by this site passes through
 *   this Service Worker. If the request URL matches a known ad/tracker domain
 *   or pattern, it is silently blocked (returns a 200 empty response so the
 *   page doesn't throw unhandled errors). All other requests pass through.
 *
 * LIMITATION:
 *   Cross-origin iframes (e.g. youtube.com) run in their own SW scope and
 *   their internal requests cannot be intercepted here. For those, use the
 *   in-page DOM observer (see stream-party.blade.php).
 */

// ─── BLOCKED DOMAINS ────────────────────────────────────────────────────────
// Sourced from EasyList, EasyPrivacy, uBlock Origin filter lists.
const AD_DOMAINS = new Set([

    // ██ Google / YouTube Ads ████████████████████████████████████████████████
    'ads.youtube.com',
    'googleads.g.doubleclick.net',
    'securepubads.g.doubleclick.net',
    'pubads.g.doubleclick.net',
    'ad.doubleclick.net',
    'static.doubleclick.net',
    'stats.g.doubleclick.net',
    'doubleclick.net',
    'googlesyndication.com',
    'googleadservices.com',
    'google-analytics.com',        // Analytics (optional — remove if you use GA)
    'imasdk.googleapis.com',       // YouTube In-stream Ad SDK ← key one
    'pagead2.googlesyndication.com',
    'tpc.googlesyndication.com',
    'fundingchoicesmessages.google.com',
    '2mdn.net',
    'ade.googlesyndication.com',
    'googletagservices.com',
    'googletagmanager.com',

    // ██ Programmatic / DSPs / SSPs ███████████████████████████████████████████
    'adnxs.com',                   // AppNexus / Xandr
    'adsrvr.org',                  // TradeDesk
    'advertising.com',
    'amazon-adsystem.com',         // Amazon Ads
    'media.net',
    'outbrain.com',
    'taboola.com',
    'revcontent.com',
    'sharethrough.com',
    'criteo.com',
    'criteo.net',
    'email.criteo.com',
    'rtb-csync.smartadserver.com',
    'smartadserver.com',
    'smaato.net',
    'openx.net',
    'openx.com',
    'pubmatic.com',
    'rubiconproject.com',          // Magnite
    'sovrn.com',
    'lijit.com',
    'casalemedia.com',             // Index Exchange
    'indexww.com',
    'appnexus.com',
    'emxdgt.com',
    'triplelift.com',
    'contextweb.com',
    'pulsepoint.com',
    '33across.com',
    'bidswitch.net',
    'yieldmo.com',
    'rhythmone.com',
    'synacor.com',
    'undertone.com',
    'teads.tv',
    'unrulymedia.com',
    'spotxchange.com',
    'spotx.tv',
    'magnite.com',
    'geniee.jp',
    'healthline.com.adsrvr.org',
    'adnium.com',
    'adform.net',
    'adform.com',
    'yieldex.com',
    'pixfuture.com',
    'adsupply.com',
    'betrad.com',                  // Ghostery
    'reflexmg.com',
    'aerserv.com',
    'conversantmedia.com',
    'conversant.com',
    'valueclickmedia.com',
    'valueclick.com',

    // ██ Video Ad Networks ████████████████████████████████████████████████████
    'freewheel.tv',
    'fwmrm.net',
    'cdn.springserve.com',
    'ads.springserve.com',
    'jwpltx.com',                  // JW Player ad tracking
    'jwplayer.com',
    'adswizz.com',
    'lkqd.net',
    'tremorhub.com',
    'vidoomy.com',
    'truex.com',
    'sprinklr.com',
    'dailymotion.com/ads',
    'vidazoo.com',
    'connatix.com',
    'playbuzz.com',
    'sendtonews.com',
    'emvantage.com',
    'iponweb.net',
    'brightroll.com',
    'yume.com',
    'vdopia.com',
    'nend.net',
    'adhese.com',

    // ██ Tracking, Analytics & Surveillance ███████████████████████████████████
    'scorecardresearch.com',
    'imrworldwide.com',            // Nielsen
    'moatads.com',
    'adsafeprotected.com',
    'doubleverify.com',
    'integral-adhoc.com',
    'iqm.com',
    'bluekai.com',
    'exelate.com',
    'krxd.net',                    // Krux / Salesforce DMP
    'addthis.com',
    'sharethis.com',
    'tynt.com',
    'histats.com',
    'chartbeat.com',
    'chartbeat.net',
    'quantserve.com',
    'quantcast.com',
    'netmng.com',
    'tealiumiq.com',
    'tiqcdn.com',
    'monetate.net',
    'bizographics.com',
    'mxpnl.com',                   // Mixpanel
    'mixpanel.com',
    'amplitude.com',
    'heap.io',
    'fullstory.com',
    'hotjar.com',                  // Heatmap / session recording
    'logrocket.com',
    'mouseflow.com',
    'clarity.ms',                  // Microsoft Clarity
    'parsely.com',
    'permutive.com',
    'lytics.io',

    // ██ Adobe / Salesforce / Oracle TrackerStack ██████████████████████████████
    'adobedtm.com',
    'demdex.net',
    'omtrdc.net',
    'everesttech.net',
    'yieldmanager.com',
    'eloqua.com',
    'marketo.net',
    'mktoresp.com',

    // ██ Social Media Trackers ████████████████████████████████████████████████
    'connect.facebook.net',        // Facebook pixel (blocks FB tracking)
    'graph.facebook.com',
    'pixel.facebook.com',
    'tr.snapchat.com',             // Snapchat pixel
    'analytics.twitter.com',       // Twitter/X pixel
    'static.ads-twitter.com',
    'bat.bing.com',                // Microsoft Bing Ads
    'ads.linkedin.com',
    'px.ads.linkedin.com',

    // ██ Crypto / Coin Mining ████████████████████████████████████████████████
    'coinhive.com',
    'coin-hive.com',
    'coinblind.com',
    'monerominer.rocks',
    'xmrpool.eu',
    'webmine.cz',
    'crypto-loot.com',
    'jjencode.com',
    'browsermine.com',

    // ██ Malware / Spyware / Adware ████████████████████████████████████████████
    'bestkywords.com',
    'findgala.com',
    'goingonearth.com',
    'grabsearch.info',
    'gumblar.cn',
    'infosyncapp.com',
    'iwebs.ws',
    'marketgid.com',
    'mediatraffic.com',
    'mgid.com',
]);

// ─── BLOCKED URL PATTERNS (substring match on full URL) ─────────────────────
const AD_PATTERNS = [
    '/pagead/',
    '/ads/api/',
    '/api/stats/ads',
    '/api/stats/atr',
    '/youtubei/v1/player/ad_break',
    '/ad_frame',
    '/preroll/',
    '/midroll/',
    '/postroll/',
    '/adserver/',
    '/banners/',
    '/advert/',
    '/sponsor/',
    'ad.js',
    'ads.js',
    'analytics.js',
    '/beacon.js',
    '/track.js',
    '/ga.js',
    '/fbevents.js',
    '//mc.yandex.ru/metrika',
    'mc.yandex.ru',
    '/counter.',
    '/pixel.',
    '/collect?',
    '/track?',
    '/log?',
];

// ─── Matching Logic ──────────────────────────────────────────────────────────
function isAdRequest(url) {
    try {
        const parsed = new URL(url);
        const hostname = parsed.hostname.toLowerCase();

        // 1. Exact domain / subdomain match
        if (AD_DOMAINS.has(hostname)) return true;
        for (const domain of AD_DOMAINS) {
            if (hostname.endsWith('.' + domain)) return true;
        }

        // 2. URL pattern substring match
        const fullUrl = url.toLowerCase();
        for (const pattern of AD_PATTERNS) {
            if (fullUrl.includes(pattern)) return true;
        }
    } catch (_) { /* ignore malformed URLs */ }
    return false;
}

// ─── Service Worker Lifecycle ────────────────────────────────────────────────
self.addEventListener('install', () => {
    console.log('[AdBlock SW] Installed — universal ad blocker active');
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    console.log('[AdBlock SW] Activated — protecting all users on this site');
    event.waitUntil(clients.claim());
});

// ─── Core: Intercept ALL network requests ────────────────────────────────────
self.addEventListener('fetch', (event) => {
    const url = event.request.url;

    if (isAdRequest(url)) {
        // Return empty 200 — pages don't break, ads just don't load
        event.respondWith(new Response('', {
            status: 200,
            statusText: 'Ad Blocked',
            headers: { 'Content-Type': 'text/plain' },
        }));
        return;
    }

    event.respondWith(fetch(event.request));
});
