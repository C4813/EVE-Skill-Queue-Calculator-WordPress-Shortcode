(function(){
    'use strict';

    // Guard: ensure data exists; provide safe defaults if not localized for any reason
    const E = window.EVE_SQC_DATA || {};
    const skillsData = E.skillsData || {};
    const defaultAttributes = E.defaultAttributes || { Intelligence:17, Memory:17, Perception:17, Willpower:17, Charisma:17 };
    const rest = E.rest || { url: '', nonce: '' };

    const spPerLevel = [0, 250, 1165, 6585, 37255, 210745];

    // Digits-only, non-negative integer for Current Skill Points
    const spInput = document.getElementById('currentSPQueue');
    if (spInput) {
        spInput.addEventListener('input', () => {
            const digitsOnly = spInput.value.replace(/[^\d]/g, '');
            if (spInput.value !== digitsOnly) spInput.value = digitsOnly;
            if (spInput.value === '') spInput.value = '0';
        });
        spInput.addEventListener('blur', () => {
            let n = parseInt(spInput.value, 10);
            if (!Number.isFinite(n) || n < 0) n = 0;
            spInput.value = String(n);
        });
        spInput.addEventListener('paste', (e) => {
            const text = (e.clipboardData || window.clipboardData).getData('text');
            if (!/^\d+$/.test(text)) e.preventDefault();
        });
        spInput.addEventListener('keydown', (e) => {
            const allowed = ['Backspace','Delete','ArrowLeft','ArrowRight','ArrowUp','ArrowDown','Home','End','Tab','Enter'];
            if (allowed.includes(e.key)) return;
            if (/^\d$/.test(e.key)) return;
            e.preventDefault();
        });
    }

    function getGain(sp, thresholds, gains) {
        for (let i = 0; i < thresholds.length; i++) {
            if (sp < thresholds[i]) return gains[i];
        }
        return gains[gains.length - 1];
    }
    function getLargeGain(sp) {
        return getGain(sp, [5000000, 50000000, 80000000], [500000, 400000, 300000, 150000]);
    }
    function getSmallGain(sp) {
        return getGain(sp, [5000000, 50000000, 80000000], [100000, 80000, 60000, 30000]);
    }
    function parseSkillsInput(input) {
        const lines = input.trim().split('\n');
        const skillLevels = {};
        for (const raw of lines) {
            const line = raw.trim();
            if (!line) continue;
            const m = line.match(/^(.+?)\s+(\d+)$/);
            if (!m) continue;
            const skillName = m[1];
            const level = parseInt(m[2], 10);
            if (!Number.isFinite(level)) continue;
            const prev = skillLevels[skillName] || 0;
            skillLevels[skillName] = Math.max(prev, level);
        }
        return skillLevels;
    }
    function calculateTotalSPfromLines(input) {
        const lines = input.trim().split('\n');
        let totalSP = 0;
        for (const raw of lines) {
            const line = raw.trim();
            if (!line) continue;
            const m = line.match(/^(.+?)\s+(\d+)$/);
            if (!m) continue;
            const skillName = m[1];
            const level = parseInt(m[2], 10);
            if (skillsData.hasOwnProperty(skillName)) {
                const multiplier = skillsData[skillName][0];
                totalSP += (spPerLevel[level] || 0) * multiplier;
            }
        }
        return totalSP;
    }

    // FIXED: keep injecting until we reach/exceed target; use small for minimal overshoot
    function calculateInjectors(currentSP, targetSP) {
        if (targetSP <= currentSP) return { large: 0, small: 0 };
        let tempSP = currentSP, largeCount = 0, smallCount = 0;

        while (tempSP < targetSP) {
            const gainLarge = getLargeGain(tempSP);
            const gainSmall = getSmallGain(tempSP);
            const remaining = targetSP - tempSP;

            if (remaining >= gainLarge) {
                largeCount++; tempSP += gainLarge;
            } else if (remaining >= gainSmall) {
                smallCount++; tempSP += gainSmall;
            } else {
                // overshoot once with a small
                smallCount++; tempSP += gainSmall;
            }
        }
        return { large: largeCount, small: smallCount };
    }

    function trainingTimeMinutes(multiplier, level, primaryAttr, secondaryAttr) {
        if (level <= 0) return 0;
        let sp = 0;
        for (let i = 1; i <= level; i++) sp += spPerLevel[i] || 0;
        sp *= multiplier;
        const spPerMinute = (primaryAttr || 0) + 0.5 * (secondaryAttr || 0);
        return spPerMinute > 0 ? sp / spPerMinute : 0;
    }
    function totalTrainingTime(skillLevels, primaryAttr, secondaryAttr) {
        let totalMinutes = 0;
        for (const skill in skillLevels) {
            if (!skillsData.hasOwnProperty(skill)) continue;
            const level = skillLevels[skill];
            const multiplier = skillsData[skill][0];
            totalMinutes += trainingTimeMinutes(multiplier, level, primaryAttr, secondaryAttr);
        }
        return totalMinutes * 60;
    }
    function formatDuration(seconds) {
        let s = Math.max(0, Math.floor(seconds));
        const d = Math.floor(s / 86400); s %= 86400;
        const h = Math.floor(s / 3600); s %= 3600;
        const m = Math.floor(s / 60); s = s % 60;
        const parts = [];
        if (d) parts.push(d + 'd');
        if (h) parts.push(h + 'h');
        if (m) parts.push(m + 'm');
        if (s) parts.push(s + 's');
        return parts.join(' ') || '0s';
    }
    function optimalAttributes(skillLevels) {
        const primaryCount = {}, secondaryCount = {};
        for (const skill in skillLevels) {
            if (!skillsData.hasOwnProperty(skill)) continue;
            const [, primary, secondary] = skillsData[skill];
            primaryCount[primary] = (primaryCount[primary] || 0) + 1;
            secondaryCount[secondary] = (secondaryCount[secondary] || 0) + 1;
        }
        const p = Object.entries(primaryCount).sort((a,b)=>b[1]-a[1]);
        const s = Object.entries(secondaryCount).sort((a,b)=>b[1]-a[1]);
        return {
            primary: p.length ? p[0][0] : 'N/A',
            secondary: s.length ? s[0][0] : 'N/A'
        };
    }

    const $ = (id) => document.getElementById(id);
    const btn = $('calcButton');
    const results = $('results');
    if (!btn || !results) return;

    async function fetchInjectorPrices() {
        // default zeros if we cannot call the endpoint
        const zeros = { largeBuy:0, largeSell:0, smallBuy:0, smallSell:0 };
        if (!rest.url) return zeros;

        try {
            const res = await fetch(rest.url, {
                method: 'GET',
                headers: { 'X-WP-Nonce': rest.nonce || '' }
            });
            if (!res.ok) return zeros;
            const data = await res.json();
            // Expect shape: {40520:{buy:int,sell:int},45635:{buy:int,sell:int}}
            const large = data && data['40520'] ? data['40520'] : data[40520];
            const small = data && data['45635'] ? data['45635'] : data[45635];
            return {
                largeBuy:  (large && Number.isFinite(large.buy)) ? large.buy : 0,
                largeSell: (large && Number.isFinite(large.sell)) ? large.sell : 0,
                smallBuy:  (small && Number.isFinite(small.buy)) ? small.buy : 0,
                smallSell: (small && Number.isFinite(small.sell)) ? small.sell : 0
            };
        } catch (e) {
            return zeros;
        }
    }

    btn.addEventListener('click', async function(){
        const inputText = $('skillsInput').value;

        let currentSP = parseInt(($('currentSPQueue').value || '0'), 10);
        if (!Number.isFinite(currentSP) || currentSP < 0) currentSP = 0;

        if (!inputText.trim()) { alert('Please paste your skills data.'); return; }

        const skillLevels = parseSkillsInput(inputText);
        if (!Object.keys(skillLevels).length) {
            alert('No valid skills found. Check your input format.');
            return;
        }

        // Fetch prices on demand (and cached server-side)
        btn.disabled = true;
        const oldLabel = btn.textContent;
        btn.textContent = 'Calculating…';
        const injectorPrices = await fetchInjectorPrices();
        btn.textContent = oldLabel;
        btn.disabled = false;

        const totalSP = calculateTotalSPfromLines(inputText);
        const targetSP = totalSP + currentSP;
        const injectors = calculateInjectors(currentSP, targetSP);

        const totalBuyCost  = injectors.large * (injectorPrices.largeBuy  || 0) + injectors.small * (injectorPrices.smallBuy  || 0);
        const totalSellCost = injectors.large * (injectorPrices.largeSell || 0) + injectors.small * (injectorPrices.smallSell || 0);

        const defaultPrimary = defaultAttributes.Intelligence || 17;
        const defaultSecondary = defaultAttributes.Memory || 17;

        const trainingSeconds = totalTrainingTime(skillLevels, defaultPrimary, defaultSecondary);
        const optAttrs = optimalAttributes(skillLevels);

        const attrForOptimal = Object.assign({}, defaultAttributes);
        if (attrForOptimal[optAttrs.primary] !== undefined) attrForOptimal[optAttrs.primary] = 27;
        if (attrForOptimal[optAttrs.secondary] !== undefined) attrForOptimal[optAttrs.secondary] = 21;

        const optPrimary = attrForOptimal[optAttrs.primary] !== undefined ? attrForOptimal[optAttrs.primary] : defaultPrimary;
        const optSecondary = attrForOptimal[optAttrs.secondary] !== undefined ? attrForOptimal[optAttrs.secondary] : defaultSecondary;

        const optTrainingSeconds = totalTrainingTime(skillLevels, optPrimary, optSecondary);
        const optTrainingSecondsPlus5 = totalTrainingTime(skillLevels, optPrimary + 5, optSecondary + 5);

        let out = '';
        out += `Total SP: ${totalSP.toLocaleString()} SP\n\n`;
        out += `Injectors needed:\n  Large: ${injectors.large}\n  Small: ${injectors.small}\n\n`;
        out += `Jita Buy: ${totalBuyCost.toLocaleString()} ISK\n`;
        out += `Jita Sell: ${totalSellCost.toLocaleString()} ISK\n\n`;
        out += `Estimated training time (default attributes): ${formatDuration(trainingSeconds)}\n\n`;
        out += `Optimal attributes:\n  Primary: ${optAttrs.primary} (27)\n  Secondary: ${optAttrs.secondary} (21)\n\n`;
        out += `Estimated training time (optimal): ${formatDuration(optTrainingSeconds)}\n`;
        out += `Estimated training time (optimal +5 implants): ${formatDuration(optTrainingSecondsPlus5)}\n`;

        results.textContent = out; // safe text-only output
    });
})();
