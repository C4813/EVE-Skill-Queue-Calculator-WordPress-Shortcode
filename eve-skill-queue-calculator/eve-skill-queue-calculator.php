<?php
/*
Plugin Name: EVE Skill Queue Calculator
Description: Adds a shortcode [eve_skill_queue_calculator] to display an EVE Online skill queue calculator to calculate required skillpoints, skill injectors, and optimal attributes.
Version: 1.2
Author: C4813
*/

defined('ABSPATH') or die('No script kiddies please!');

// Load skills data from JSON file
$skills_data_file = plugin_dir_path(__FILE__) . 'skills_data.json';
$skills_data_json = file_get_contents($skills_data_file);
$skills_data = json_decode($skills_data_json, true);

if (!$skills_data) {
    $skills_data = []; // fallback empty array if JSON load fails
}

// Default attributes for calculations
$default_attributes = [
    "Intelligence" => 17,
    "Perception" => 17,
    "Willpower" => 17,
    "Memory" => 17,
    "Charisma" => 17,
];

function eve_skill_queue_calculator_shortcode() {
    global $skills_data, $default_attributes;

    $prices = fetch_injector_prices();

    $largeBuy = $prices[40520]['buy'] ?? 0;
    $largeSell = $prices[40520]['sell'] ?? 0;
    $smallBuy = $prices[45635]['buy'] ?? 0;
    $smallSell = $prices[45635]['sell'] ?? 0;

    ob_start();
    ?>
    <div id="queue-calculator" style="padding:1em; max-width:700px; font-family: Arial, sans-serif; margin: 0 auto; text-align: center;">
        <h2>Skill Queue Calculator</h2>
        <p>Paste your skills below (format: <code>SkillName Level</code>, one per line):</p>
        <textarea id="skillsInput" rows="12" style="width:100%; font-family: monospace;"></textarea>
        <p>
            <label for="currentSPQueue">Current Skill Points Queue:</label>
            <input type="number" id="currentSPQueue" value="0" min="0" style="width:150px;">
        </p>
        <button id="calcButton" style="padding: 0.5em 1em;">Calculate</button>

        <div id="results" style="margin-top:1em; padding:1em; white-space: normal; font-family: Arial, sans-serif;"></div>
    </div>

    <script>
    const injectorPrices = {
        largeBuy: <?php echo $largeBuy; ?>,
        largeSell: <?php echo $largeSell; ?>,
        smallBuy: <?php echo $smallBuy; ?>,
        smallSell: <?php echo $smallSell; ?>
    };

    (function(){
        const skillsData = <?php echo json_encode($skills_data); ?>;
        const defaultAttributes = <?php echo json_encode($default_attributes); ?>;

        const spPerLevel = [0, 250, 1165, 6585, 37255, 210745];

        function getLargeGain(sp) {
            if (sp < 5000000) return 500000;
            if (sp < 50000000) return 400000;
            if (sp < 80000000) return 300000;
            return 150000;
        }
        
        function getSmallGain(sp) {
            if (sp < 5000000) return 100000;
            if (sp < 50000000) return 80000;
            if (sp < 80000000) return 60000;
            return 30000;
        }

        function parseSkillsInput(input) {
            const lines = input.trim().split('\n');
            let skillLevels = {};
            for(let line of lines) {
                line = line.trim();
                if(!line) continue;
                const match = line.match(/^(.+?)\s+(\d+)$/);
                if(match) {
                    let skillName = match[1];
                    let level = parseInt(match[2], 10);
                    if(skillName in skillLevels) {
                        skillLevels[skillName] = Math.max(skillLevels[skillName], level);
                    } else {
                        skillLevels[skillName] = level;
                    }
                }
            }
            return skillLevels;
        }

        function calculateTotalSPfromLines(input) {
            const lines = input.trim().split('\n');
            let totalSP = 0;
            for(let line of lines) {
                line = line.trim();
                if(!line) continue;
                const match = line.match(/^(.+?)\s+(\d+)$/);
                if(match) {
                    let skillName = match[1];
                    let level = parseInt(match[2], 10);
                    if(skillName in skillsData) {
                        const multiplier = skillsData[skillName][0];
                        totalSP += spPerLevel[level] * multiplier;
                    }
                }
            }
            return totalSP;
        }

        function calculateInjectors(currentSP, targetSP) {
            if (targetSP <= currentSP) return { large: 0, small: 0 };
            let remainingSP = targetSP - currentSP;
            let largeCount = 0;
            let smallCount = 0;
            let tempSP = currentSP;

            while (true) {
                let gainLarge = getLargeGain(tempSP);
                if (gainLarge <= 0) break;

                if (gainLarge <= remainingSP) {
                    largeCount++;
                    tempSP += gainLarge;
                    remainingSP -= gainLarge;
                } else {
                    break;
                }
            }

            while (true) {
                let gainSmall = getSmallGain(tempSP);
                if (gainSmall <= 0) break;

                if (gainSmall <= remainingSP) {
                    smallCount++;
                    tempSP += gainSmall;
                    remainingSP -= gainSmall;
                } else {
                    break;
                }
            }

            return { large: largeCount, small: smallCount };
        }

        function trainingTimeMinutes(multiplier, level, primaryAttr, secondaryAttr) {
            if(level <= 0) return 0;
            let sp = 0;
            for(let i = 1; i <= level; i++) {
                sp += spPerLevel[i];
            }
            sp *= multiplier;
            let spPerMinute = primaryAttr + 0.5 * secondaryAttr;
            return sp / spPerMinute;
        }

        function totalTrainingTime(skillLevels, primaryAttr, secondaryAttr) {
            let totalMinutes = 0;
            for(const skill in skillLevels) {
                const level = skillLevels[skill];
                if(skill in skillsData) {
                    const multiplier = skillsData[skill][0];
                    totalMinutes += trainingTimeMinutes(multiplier, level, primaryAttr, secondaryAttr);
                }
            }
            return totalMinutes * 60;
        }

        function formatDuration(seconds) {
            let d = Math.floor(seconds / 86400);
            seconds %= 86400;
            let h = Math.floor(seconds / 3600);
            seconds %= 3600;
            let m = Math.floor(seconds / 60);
            let s = Math.floor(seconds % 60);
            let parts = [];
            if(d) parts.push(d + "d");
            if(h) parts.push(h + "h");
            if(m) parts.push(m + "m");
            if(s) parts.push(s + "s");
            return parts.join(' ') || "0s";
        }

        function optimalAttributes(skillLevels) {
            let primaryCount = {};
            let secondaryCount = {};
            for(const skill in skillLevels) {
                if(skill in skillsData) {
                    let [_, primary, secondary] = skillsData[skill];
                    primaryCount[primary] = (primaryCount[primary] || 0) + 1;
                    secondaryCount[secondary] = (secondaryCount[secondary] || 0) + 1;
                }
            }
            let sortedPrimary = Object.entries(primaryCount).sort((a,b) => b[1] - a[1]);
            let sortedSecondary = Object.entries(secondaryCount).sort((a,b) => b[1] - a[1]);

            return {
                primary: sortedPrimary.length ? sortedPrimary[0][0] : 'N/A',
                secondary: sortedSecondary.length ? sortedSecondary[0][0] : 'N/A'
            };
        }

        document.getElementById('calcButton').addEventListener('click', function(){
            const inputText = document.getElementById('skillsInput').value;
            const currentSP = Number(document.getElementById('currentSPQueue').value) || 0;

            if(!inputText.trim()) {
                alert("Please paste your skills data.");
                return;
            }
            const skillLevels = parseSkillsInput(inputText);

            if(Object.keys(skillLevels).length === 0) {
                alert("No valid skills found. Check your input format.");
                return;
            }

            const totalSP = calculateTotalSPfromLines(inputText);
            const targetSP = totalSP + currentSP;
            const injectors = calculateInjectors(currentSP, targetSP);

            // Calculate ISK costs
            const totalBuyCost = injectors.large * injectorPrices.largeBuy + injectors.small * injectorPrices.smallBuy;
            const totalSellCost = injectors.large * injectorPrices.largeSell + injectors.small * injectorPrices.smallSell;

            // Default attributes for regular calculation
            const defaultPrimary = defaultAttributes['Intelligence'] || 17;
            const defaultSecondary = defaultAttributes['Memory'] || 17;

            const trainingSeconds = totalTrainingTime(skillLevels, defaultPrimary, defaultSecondary);

            const optAttrs = optimalAttributes(skillLevels);

            let attrForOptimal = {...defaultAttributes};
            attrForOptimal[optAttrs.primary] = 27;
            attrForOptimal[optAttrs.secondary] = 21;

            const optTrainingSeconds = totalTrainingTime(skillLevels, attrForOptimal[optAttrs.primary], attrForOptimal[optAttrs.secondary]);

            const optTrainingSecondsPlus5 = totalTrainingTime(
                skillLevels,
                attrForOptimal[optAttrs.primary] + 5,
                attrForOptimal[optAttrs.secondary] + 5
            );

            let resultText = "";
            resultText += `<strong>Total SP</strong>: ${totalSP.toLocaleString()} SP\n\n`;
            resultText += `<strong>Injectors needed</strong>:\n<strong>Large</strong>: ${injectors.large}\n<strong>Small</strong>: ${injectors.small}\n\n`;
            resultText += `<strong>Jita Buy</strong>: ${totalBuyCost.toLocaleString()} ISK\n`;
            resultText += `<strong>Jita Sell</strong>: ${totalSellCost.toLocaleString()} ISK\n\n`;
            resultText += `<strong>Estimated training time</strong> (default attributes): ${formatDuration(trainingSeconds)}\n\n`;
            resultText += `<strong>Optimal attributes</strong>:\n<strong>Primary</strong>: ${optAttrs.primary} (27)\n<strong>Secondary</strong>: ${optAttrs.secondary} (21)\n\n`;
            resultText += `<strong>Estimated training time</strong> (optimal attributes 27/21): ${formatDuration(optTrainingSeconds)}\n`;
            resultText += `<strong>Estimated training time</strong> (optimal attributes +5 implants): ${formatDuration(optTrainingSecondsPlus5)}\n`;

            document.getElementById('results').innerHTML = resultText.replace(/\n/g, '<br>');

        });
    })();
    </script>
    <?php
    return ob_get_clean();
}

add_shortcode('eve_skill_queue_calculator', 'eve_skill_queue_calculator_shortcode');
