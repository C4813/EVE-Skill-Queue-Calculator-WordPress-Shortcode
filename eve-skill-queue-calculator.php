<?php
/*
Plugin Name: EVE Skill Queue Calculator
Description: Adds a shortcode [eve_skill_queue_calculator] to display an EVE Online skill queue calculator to calculate required skillpoints, skill injectors, and optimal attributes.
Version: 1
Author: C4813
*/

defined('ABSPATH') or die('No script kiddies please!');

// Full skill data array (replace with your full 479 skills array)
$skills_data = [
    "Armor Layering" => [3, "Intelligence", "Memory"],
    "Capital Remote Armor Repair Systems" => [10, "Intelligence", "Memory"],
    "Capital Remote Hull Repair Systems" => [10, "Intelligence", "Memory"],
    "Capital Repair Systems" => [8, "Intelligence", "Memory"],
    "EM Armor Compensation" => [2, "Intelligence", "Memory"],
    "Explosive Armor Compensation" => [2, "Intelligence", "Memory"],
    "Hull Upgrades" => [2, "Intelligence", "Memory"],
    "Kinetic Armor Compensation" => [2, "Intelligence", "Memory"],
    "Mechanics" => [1, "Intelligence", "Memory"],
    "Remote Armor Repair Systems" => [2, "Intelligence", "Memory"],
    "Remote Hull Repair Systems" => [2, "Intelligence", "Memory"],
    "Repair Systems" => [1, "Intelligence", "Memory"],
    "Thermal Armor Compensation" => [2, "Intelligence", "Memory"],
];
$skills_data += [
    "Corporation Management" => [1, "Memory", "Charisma"],
    "Diplomatic Relations" => [2, "Memory", "Charisma"],
    "Empire Control" => [5, "Memory", "Charisma"],
    "Megacorp Management" => [3, "Memory", "Charisma"],
    "Sovereignty" => [8, "Memory", "Charisma"],
];
$skills_data += [
    "Corporation Management" => [1, "Memory", "Charisma"],
    "Diplomatic Relations" => [2, "Memory", "Charisma"],
    "Empire Control" => [5, "Memory", "Charisma"],
    "Megacorp Management" => [3, "Memory", "Charisma"],
    "Sovereignty" => [8, "Memory", "Charisma"],
];
$skills_data += [
    "Advanced Drone Avionics" => [5, "Memory", "Perception"],
    "Amarr Drone Specialization" => [5, "Memory", "Perception"],
    "Caldari Drone Specialization" => [5, "Memory", "Perception"],
    "Drone Avionics" => [1, "Memory", "Perception"],
    "Drone Durability" => [5, "Memory", "Perception"],
    "Drone Interfacing" => [5, "Memory", "Perception"],
    "Drone Navigation" => [1, "Memory", "Perception"],
    "Drone Sharpshooting" => [1, "Memory", "Perception"],
    "Drones" => [1, "Memory", "Perception"],
    "Fighter Hangar Management" => [8, "Memory", "Perception"],
    "Fighters" => [12, "Memory", "Perception"],
    "Gallente Drone Specialization" => [5, "Memory", "Perception"],
    "Heavy Drone Operation" => [5, "Memory", "Perception"],
    "Heavy Fighters" => [12, "Memory", "Perception"],
    "Ice Harvesting Drone Operation" => [2, "Memory", "Perception"],
    "Ice Harvesting Drone Specialization" => [5, "Memory", "Perception"],
    "Light Drone Operation" => [1, "Memory", "Perception"],
    "Light Fighters" => [12, "Memory", "Perception"],
    "Medium Drone Operation" => [2, "Memory", "Perception"],
    "Mining Drone Operation" => [2, "Memory", "Perception"],
    "Mining Drone Specialization" => [5, "Memory", "Perception"],
    "Minmatar Drone Specialization" => [5, "Memory", "Perception"],
    "Mutated Drone Specialization" => [6, "Memory", "Perception"],
    "Repair Drone Operation" => [3, "Memory", "Perception"],
    "Salvage Drone Operation" => [4, "Memory", "Perception"],
    "Salvage Drone Specialization" => [5, "Memory", "Perception"],
    "Sentry Drone Interfacing" => [5, "Memory", "Perception"],
    "Support Fighters" => [12, "Memory", "Perception"],
];
$skills_data += [
    "Burst Projector Operation" => [8, "Intelligence", "Memory"],
    "Cloaking" => [6, "Intelligence", "Memory"],
    "Electronic Warfare" => [2, "Intelligence", "Memory"],
    "Frequency Modulation" => [3, "Intelligence", "Memory"],
    "Long Distance Jamming" => [4, "Intelligence", "Memory"],
    "Propulsion Jamming" => [3, "Intelligence", "Memory"],
    "Sensor Linking" => [3, "Intelligence", "Memory"],
    "Signal Dispersion" => [5, "Intelligence", "Memory"],
    "Signal Suppression" => [5, "Intelligence", "Memory"],
    "Signature Focusing" => [5, "Intelligence", "Memory"],
    "Signature Masking" => [5, "Intelligence", "Memory"],
    "Tactical Logistics Reconfiguration" => [8, "Intelligence", "Memory"],
    "Target Painting" => [3, "Intelligence", "Memory"],
    "Weapon Destabilization" => [5, "Intelligence", "Memory"],
    "Weapon Disruption" => [3, "Intelligence", "Memory"],
];
$skills_data += [
    "Advanced Weapon Upgrades" => [6, "Perception", "Willpower"],
    "CPU Management" => [1, "Intelligence", "Memory"],
    "Capacitor Emission Systems" => [2, "Intelligence", "Memory"],
    "Capacitor Management" => [3, "Intelligence", "Memory"],
    "Capacitor Systems Operation" => [1, "Intelligence", "Memory"],
    "Capital Capacitor Emission Systems" => [10, "Intelligence", "Memory"],
    "Electronics Upgrades" => [2, "Intelligence", "Memory"],
    "Energy Grid Upgrades" => [2, "Intelligence", "Memory"],
    "Energy Pulse Weapons" => [2, "Intelligence", "Memory"],
    "Nanite Interfacing" => [3, "Intelligence", "Memory"],
    "Nanite Operation" => [2, "Intelligence", "Memory"],
    "Power Grid Management" => [1, "Intelligence", "Memory"],
    "Resistance Phasing" => [3, "Intelligence", "Memory"],
    "Thermodynamics" => [3, "Intelligence", "Memory"],
    "Weapon Upgrades" => [2, "Perception", "Memory"],
];
$skills_data += [
    "Armored Command" => [2, "Charisma", "Willpower"],
    "Armored Command Specialist" => [5, "Charisma", "Willpower"],
    "Command Burst Specialist" => [6, "Charisma", "Willpower"],
    "Fleet Command" => [12, "Charisma", "Willpower"],
    "Fleet Compression Logistics" => [4, "Charisma", "Willpower"],
    "Fleet Coordination" => [8, "Charisma", "Willpower"],
    "Fleet Formations" => [5, "Charisma", "Willpower"],
    "Information Command" => [2, "Charisma", "Willpower"],
    "Information Command Specialist" => [5, "Charisma", "Willpower"],
    "Leadership" => [1, "Charisma", "Willpower"],
    "Mining Director" => [5, "Charisma", "Willpower"],
    "Mining Foreman" => [2, "Charisma", "Willpower"],
    "Shield Command" => [2, "Charisma", "Willpower"],
    "Shield Command Specialist" => [5, "Charisma", "Willpower"],
    "Skirmish Command" => [2, "Charisma", "Willpower"],
    "Skirmish Command Specialist" => [5, "Charisma", "Willpower"],
    "Spatial Phenomena Generation" => [10, "Charisma", "Willpower"],
    "Wing Command" => [8, "Charisma", "Willpower"],
];
$skills_data += [
    "Advanced Doomsday Operation" => [14, "Willpower", "Intelligence"],
    "Capital Artillery Specialization" => [10, "Perception", "Willpower"],
    "Capital Autocannon Specialization" => [10, "Perception", "Willpower"],
    "Capital Beam Laser Specialization" => [10, "Perception", "Willpower"],
    "Capital Blaster Specialization" => [10, "Perception", "Willpower"],
    "Capital Energy Turret" => [7, "Perception", "Willpower"],
    "Capital Hybrid Turret" => [7, "Perception", "Willpower"],
    "Capital Precursor Weapon" => [7, "Perception", "Willpower"],
    "Capital Projectile Turret" => [7, "Perception", "Willpower"],
    "Capital Pulse Laser Specialization" => [10, "Perception", "Willpower"],
    "Capital Railgun Specialization" => [10, "Perception", "Willpower"],
    "Controlled Bursts" => [2, "Perception", "Willpower"],
    "Disruptive Lance Operation" => [14, "Willpower", "Intelligence"],
    "Doomsday Operation" => [14, "Willpower", "Intelligence"],
    "Doomsday Rapid Firing" => [14, "Willpower", "Intelligence"],
    "Gunnery" => [1, "Perception", "Willpower"],
    "Large Artillery Specialization" => [8, "Perception", "Willpower"],
    "Large Autocannon Specialization" => [8, "Perception", "Willpower"],
    "Large Beam Laser Specialization" => [8, "Perception", "Willpower"],
    "Large Blaster Specialization" => [8, "Perception", "Willpower"],
    "Large Disintegrator Specialization" => [8, "Perception", "Willpower"],
    "Large Energy Turret" => [5, "Perception", "Willpower"],
    "Large Hybrid Turret" => [5, "Perception", "Willpower"],
    "Large Precursor Weapon" => [5, "Perception", "Willpower"],
    "Large Projectile Turret" => [5, "Perception", "Willpower"],
    "Large Pulse Laser Specialization" => [8, "Perception", "Willpower"],
    "Large Railgun Specialization" => [8, "Perception", "Willpower"],
    "Large Vorton Projector" => [5, "Perception", "Willpower"],
    "Large Vorton Specialization" => [8, "Perception", "Willpower"],
    "Medium Artillery Specialization" => [5, "Perception", "Willpower"],
    "Medium Autocannon Specialization" => [5, "Perception", "Willpower"],
    "Medium Beam Laser Specialization" => [5, "Perception", "Willpower"],
    "Medium Blaster Specialization" => [5, "Perception", "Willpower"],
    "Medium Disintegrator Specialization" => [5, "Perception", "Willpower"],
    "Medium Energy Turret" => [3, "Perception", "Willpower"],
    "Medium Hybrid Turret" => [3, "Perception", "Willpower"],
    "Medium Precursor Weapon" => [3, "Perception", "Willpower"],
    "Medium Projectile Turret" => [3, "Perception", "Willpower"],
    "Medium Pulse Laser Specialization" => [5, "Perception", "Willpower"],
    "Medium Railgun Specialization" => [5, "Perception", "Willpower"],
    "Medium Vorton Projector" => [3, "Perception", "Willpower"],
    "Medium Vorton Specialization" => [5, "Perception", "Willpower"],
    "Motion Prediction" => [2, "Perception", "Willpower"],
    "Rapid Firing" => [2, "Perception", "Willpower"],
    "Sharpshooter" => [2, "Perception", "Willpower"],
    "Small Artillery Specialization" => [3, "Perception", "Willpower"],
    "Small Autocannon Specialization" => [3, "Perception", "Willpower"],
    "Small Beam Laser Specialization" => [3, "Perception", "Willpower"],
    "Small Blaster Specialization" => [3, "Perception", "Willpower"],
    "Small Disintegrator Specialization" => [3, "Perception", "Willpower"],
    "Small Energy Turret" => [1, "Perception", "Willpower"],
    "Small Hybrid Turret" => [1, "Perception", "Willpower"],
    "Small Precursor Weapon" => [1, "Perception", "Willpower"],
    "Small Projectile Turret" => [1, "Perception", "Willpower"],
    "Small Pulse Laser Specialization" => [3, "Perception", "Willpower"],
    "Small Railgun Specialization" => [3, "Perception", "Willpower"],
    "Small Vorton Projector" => [1, "Perception", "Willpower"],
    "Small Vorton Specialization" => [3, "Perception", "Willpower"],
    "Surgical Strike" => [4, "Perception", "Willpower"],
    "Tactical Weapon Reconfiguration" => [8, "Perception", "Willpower"],
    "Trajectory Analysis" => [5, "Perception", "Willpower"],
    "Vorton Arc Extension" => [2, "Perception", "Willpower"],
    "Vorton Arc Guidance" => [2, "Perception", "Willpower"],
    "Vorton Power Amplification" => [4, "Perception", "Willpower"],
    "Vorton Projector Operation" => [1, "Perception", "Willpower"],
];
$skills_data += [
    "Auto-Targeting Missiles" => [3, "Perception", "Willpower"],
    "Bomb Deployment" => [4, "Perception", "Willpower"],
    "Breacher Pod Clone Efficacity" => [5, "Willpower", "Charisma"],
    "Breacher Pod Clone Longevity" => [7, "Willpower", "Charisma"],
    "Breacher Pod Launcher Opration" => [3, "Willpower", "Charisma"],
    "Breacher Pod Projection" => [5, "Willpower", "Charisma"],
    "Breacher Pod Rapid Firing" => [5, "Willpower", "Charisma"],
    "Cruise Missile Specialization" => [8, "Perception", "Willpower"],
    "Cruise Missiles" => [5, "Perception", "Willpower"],
    "Defender Missiles" => [2, "Perception", "Willpower"],
    "Guided Missile Precision" => [5, "Perception", "Willpower"],
    "Heavy Assault Missile Specialization" => [5, "Perception", "Willpower"],
    "Heavy Assault Missiles" => [3, "Perception", "Willpower"],
    "Heavy Missile Specialization" => [5, "Perception", "Willpower"],
    "Heavy Missiles" => [3, "Perception", "Willpower"],
    "Light Missile Specialization" => [3, "Perception", "Willpower"],
    "Light Missiles" => [2, "Perception", "Willpower"],
    "Missile Bombardment" => [2, "Perception", "Willpower"],
    "Missile Launcher Operation" => [1, "Perception", "Willpower"],
    "Missile Projection" => [4, "Perception", "Willpower"],
    "Rapid Launch" => [2, "Perception", "Willpower"],
    "Rocket Specialization" => [3, "Perception", "Willpower"],
    "Rockets" => [1, "Perception", "Willpower"],
    "Target Navigation Prediction" => [2, "Perception", "Willpower"],
    "Torpedo Specialization" => [8, "Perception", "Willpower"],
    "Torpedoes" => [4, "Perception", "Willpower"],
    "Warhead Upgrades" => [5, "Perception", "Willpower"],
    "XL Cruise Missile Specialization" => [10, "Perception", "Willpower"],
    "XL Cruise Missiles" => [7, "Perception", "Willpower"],
    "XL Torpedo Specialization" => [10, "Perception", "Willpower"],
    "XL Torpedoes" => [7, "Perception", "Willpower"],
];
$skills_data += [
    "Acceleration Control" => [4, "Intelligence", "Perception"],
    "Afterburner" => [1, "Intelligence", "Perception"],
    "Capital Jump Portal Generation" => [14, "Intelligence", "Memory"],
    "Capital Micro Jumpo Drive Operation" => [14, "Intelligence", "Perception"],
    "Cynosural Field Theory" => [5, "Intelligence", "Memory"],
    "Evasive Maneuvering" => [2, "Intelligence", "Perception"],
    "Fuel Conservation" => [2, "Intelligence", "Perception"],
    "High Speed Maneuvering" => [5, "Intelligence", "Perception"],
    "Jump Drive Calibration" => [9, "Intelligence", "Perception"],
    "Jump Drive Operation" => [5, "Intelligence", "Perception"],
    "Jump Fuel Conservation" => [8, "Intelligence", "Perception"],
    "Jump Portal Generation" => [14, "Intelligence", "Memory"],
    "Micro Jump Drive Operation" => [5, "Intelligence", "Perception"],
    "Navigation" => [1, "Intelligence", "Perception"],
    "Warp Drive Operation" => [1, "Intelligence", "Perception"],
];
$skills_data += [
    "Advanced Infomorph Psychology" => [5, "Charisma", "Willpower"],
    "Biology" => [1, "Intelligence", "Memory"],
    "Cloning Facility Operation" => [10, "Intelligence", "Memory"],
    "Cybernetics" => [3, "Intelligence", "Memory"],
    "Elite Infomorph Psychology" => [12, "Charisma", "Willpower"],
    "Infomorph Psychology" => [1, "Charisma", "Willpower"],
    "Infomorph Synchronizing" => [2, "Charisma", "Willpower"],
    "Neurotoxin Control" => [2, "Intelligence", "Memory"],
    "Neurotoxin Recovery" => [5, "Intelligence", "Memory"],
];
$skills_data += [
    "Advanced Planetology" => [5, "Intelligence", "Memory"],
    "Command Center Upgrades" => [4, "Charisma", "Intelligence"],
    "Interplanetary Consolidation" => [4, "Charisma", "Intelligence"],
    "Planetology" => [3, "Intelligence", "Memory"],
    "Remote Sensing" => [1, "Intelligence", "Memory"],
];
$skills_data += [
    "Advanced Capital Ship Construction" => [16, "Intelligence", "Memory"],
    "Advanced Industrial Ship Construction" => [3, "Intelligence", "Memory"],
    "Advanced Industry" => [3, "Memory", "Intelligence"],
    "Advanced Large Ship Construction" => [8, "Intelligence", "Memory"],
    "Advanced Mass Production" => [8, "Memory", "Intelligence"],
    "Advanced Medium Ship Construction" => [5, "Intelligence", "Memory"],
    "Advanced Small Ship Construction" => [2, "Intelligence", "Memory"],
    "Capital Ship Construction" => [14, "Intelligence", "Memory"],
    "Drug Manufacturing" => [2, "Memory", "Intelligence"],
    "Industry" => [1, "Memory", "Intelligence"],
    "Mass Production" => [2, "Memory", "Intelligence"],
    "Outpost Construction" => [16, "Intelligence", "Memory"],
    "Supply Chain Management" => [3, "Memory", "Intelligence"],
];
$skills_data += [
    "Abyssal Ore Processing" => [12, "Memory", "Intelligence"],
    "Advanced Mass Reactions" => [8, "Memory", "Intelligence"],
    "Astrogeology" => [3, "Intelligence", "Memory"],
    "Capital Industrial Reconfiguration" => [8, "Memory", "Intelligence"],
    "Capital Shipboard Compression Technology" => [3, "Memory", "Intelligence"],
    "Coherent Ore Processing" => [6, "Memory", "Intelligence"],
    "Common Moon Ore Processing" => [5, "Memory", "Intelligence"],
    "Complex Ore Processing" => [11, "Memory", "Intelligence"],
    "Deep Core Mining" => [6, "Memory", "Intelligence"],
    "Exceptional Moon Ore Processing" => [8, "Memory", "Intelligence"],
    "Gas Cloud Harvesting" => [1, "Memory", "Intelligence"],
    "Gas Decompression Efficiency" => [2, "Memory", "Intelligence"],
    "Ice Harvesting" => [1, "Memory", "Intelligence"],
    "Ice Processing" => [5, "Memory", "Intelligence"],
    "Industrial Reconfiguration" => [4, "Memory", "Intelligence"],
    "Mass Reactions" => [2, "Memory", "Intelligence"],
    "Mercoxit Ore Processing" => [5, "Memory", "Intelligence"],
    "Mining" => [1, "Memory", "Intelligence"],
    "Mining Upgrades" => [4, "Memory", "Intelligence"],
    "Rare Moon Ore Processing" => [7, "Memory", "Intelligence"],
    "Reactions" => [1, "Memory", "Intelligence"],
    "Remote Reactions" => [3, "Memory", "Intelligence"],
    "Reprocessing" => [1, "Memory", "Intelligence"],
    "Reprocessing Efficiency" => [3, "Memory", "Intelligence"],
    "Salvaging" => [3, "Memory", "Intelligence"],
    "Scrapmetal Processing" => [5, "Memory", "Intelligence"],
    "Shipboard Compression Technology" => [3, "Memory", "Intelligence"],
    "Simple Ore Processing" => [3, "Memory", "Intelligence"],
    "Ubiquitous Moon Ore Processing" => [4, "Memory", "Intelligence"],
    "Uncommon Moon Ore Processing" => [6, "Memory", "Intelligence"],
    "Variegated Ore Processing" => [9, "Memory", "Intelligence"],
];
$skills_data += [
    "Armor Rigging" => [3, "Intelligence", "Memory"],
    "Astronautics Rigging" => [3, "Intelligence", "Memory"],
    "Drones Rigging" => [3, "Intelligence", "Memory"],
    "Electronic Superiority Rigging" => [3, "Intelligence", "Memory"],
    "Energy Weapon Rigging" => [3, "Intelligence", "Memory"],
    "Hybrid Weapon Rigging" => [3, "Intelligence", "Memory"],
    "Jury Rigging" => [2, "Intelligence", "Memory"],
    "Launcher Rigging" => [3, "Intelligence", "Memory"],
    "Projectile Weapon Rigging" => [3, "Intelligence", "Memory"],
    "Shield Rigging" => [3, "Intelligence", "Memory"],
];
$skills_data += [
    "Archaeology" => [3, "Intelligence", "Memory"],
    "Astrometric Acquisition" => [5, "Intelligence", "Memory"],
    "Astrometric Pinpointing" => [5, "Intelligence", "Memory"],
    "Astrometric Rangefinding" => [8, "Intelligence", "Memory"],
    "Astrometrics" => [3, "Intelligence", "Memory"],
    "Hacking" => [3, "Intelligence", "Memory"],
    "Survey" => [1, "Intelligence", "Memory"],
];
$skills_data += [
    "Advanced Laboratory Operation" => [8, "Intelligence", "Memory"],
    "Amarr Encryption Methods" => [5, "Intelligence", "Memory"],
    "Amarr Starship Engineering" => [5, "Intelligence", "Memory"],
    "Astronautic Engineering" => [5, "Intelligence", "Memory"],
    "Caldari Encryption Methods" => [5, "Intelligence", "Memory"],
    "Caldari Starship Engineering" => [5, "Intelligence", "Memory"],
    "Core Subsystem Technology" => [5, "Intelligence", "Memory"],
    "Defensive Subsystem Technology" => [5, "Intelligence", "Memory"],
    "Electromagnetic Physics" => [5, "Intelligence", "Memory"],
    "Electronic Engineering" => [5, "Intelligence", "Memory"],
    "Gallente Encryption Methods" => [5, "Intelligence", "Memory"],
    "Gallente Starship Engineering" => [5, "Intelligence", "Memory"],
    "Graviton Physics" => [5, "Intelligence", "Memory"],
    "High Energy Physics" => [5, "Intelligence", "Memory"],
    "Hydromagnetic Physics" => [5, "Intelligence", "Memory"],
    "Laboratory Operation" => [1, "Intelligence", "Memory"],
    "Laser Physics" => [5, "Intelligence", "Memory"],
    "Mechanical Engineering" => [5, "Intelligence", "Memory"],
    "Metallurgy" => [3, "Intelligence", "Memory"],
    "Minmatar Encryption Methods" => [5, "Intelligence", "Memory"],
    "Minmatar Starship Engineering" => [5, "Intelligence", "Memory"],
    "Molecular Engineering" => [5, "Intelligence", "Memory"],
    "Mutagenic Stabilization" => [2, "Memory", "Intelligence"],
    "Nanite Engineering" => [5, "Intelligence", "Memory"],
    "Nuclear Physics" => [5, "Intelligence", "Memory"],
    "Offensive Subsystem Technology" => [5, "Intelligence", "Memory"],
    "Plasma Physics" => [5, "Intelligence", "Memory"],
    "Propulsion Subsystem Technology" => [5, "Intelligence", "Memory"],
    "Quantum Physics" => [5, "Intelligence", "Memory"],
    "Research" => [1, "Intelligence", "Memory"],
    "Research Project Management" => [8, "Memory", "Charisma"],
    "Rocket Science" => [5, "Intelligence", "Memory"],
    "Science" => [1, "Intelligence", "Memory"],
    "Scientific Networking" => [3, "Intelligence", "Memory"],
    "Sleeper Encryption Methods" => [4, "Intelligence", "Memory"],
    "Sleeper Technology" => [5, "Intelligence", "Memory"],
    "Takmahl Technology" => [5, "Intelligence", "Memory"],
    "Talocan Technology" => [5, "Intelligence", "Memory"],
    "Triglavian Encryption Methods" => [5, "Intelligence", "Memory"],
    "Triglavian Quantum Engineering" => [5, "Intelligence", "Memory"],
    "Upwell Encryption Methods" => [5, "Intelligence", "Memory"],
];
$skills_data += [
    "Capital Shield Emission Systems" => [10, "Intelligence", "Memory"],
    "Capital Shield Operation" => [8, "Intelligence", "Memory"],
    "EM Shield Compensation" => [2, "Intelligence", "Memory"],
    "Explosive Shield Compensation" => [2, "Intelligence", "Memory"],
    "Invulnerability Core Operation" => [8, "Intelligence", "Memory"],
    "Kinetic Shield Compensation" => [2, "Intelligence", "Memory"],
    "Shield Compensation" => [2, "Intelligence", "Memory"],
    "Shield Emission Systems" => [2, "Intelligence", "Memory"],
    "Shield Management" => [3, "Intelligence", "Memory"],
    "Shield Operation" => [1, "Intelligence", "Memory"],
    "Shield Upgrades" => [2, "Intelligence", "Memory"],
    "Tactical Shield Manipulation" => [4, "Intelligence", "Memory"],
    "Thermal Shield Compensation" => [2, "Intelligence", "Memory"],
];
$skills_data += [
    "Connections" => [3, "Charisma", "Intelligence"],
    "Criminal Connections" => [3, "Charisma", "Intelligence"],
    "Diplomacy" => [1, "Charisma", "Intelligence"],
    "Distribution Connections" => [2, "Charisma", "Intelligence"],
    "Fast Talk" => [4, "Charisma", "Intelligence"],
    "Mining Connections" => [2, "Charisma", "Intelligence"],
    "Negotiation" => [2, "Charisma", "Intelligence"],
    "Security Connections" => [2, "Charisma", "Intelligence"],
    "Social" => [1, "Charisma", "Intelligence"],
];
$skills_data += [
    "Advanced Spaceship Command" => [5, "Perception", "Willpower"],
    "Amarr Battlecruiser" => [6, "Perception", "Willpower"],
    "Amarr Battleship" => [8, "Perception", "Willpower"],
    "Amarr Carrier" => [14, "Perception", "Willpower"],
    "Amarr Cruiser" => [5, "Perception", "Willpower"],
    "Amarr Destroyer" => [2, "Perception", "Willpower"],
    "Amarr Dreadnought" => [12, "Perception", "Willpower"],
    "Amarr Freighter" => [10, "Perception", "Willpower"],
    "Amarr Frigate" => [2, "Perception", "Willpower"],
    "Amarr Hauler" => [4, "Perception", "Willpower"],
    "Amarr Strategic Cruiser" => [5, "Perception", "Willpower"],
    "Amarr Tactical Destroyer" => [3, "Perception", "Willpower"],
    "Amarr Titan" => [16, "Perception", "Willpower"],
    "Assault Frigates" => [4, "Willpower", "Perception"],
    "Black Ops" => [10, "Willpower", "Perception"],
    "Caldari Battlecruiser" => [6, "Perception", "Willpower"],
    "Caldari Battleship" => [8, "Perception", "Willpower"],
    "Caldari Carrier" => [14, "Perception", "Willpower"],
    "Caldari Cruiser" => [5, "Perception", "Willpower"],
    "Caldari Destroyer" => [2, "Perception", "Willpower"],
    "Caldari Dreadnought" => [12, "Perception", "Willpower"],
    "Caldari Freighter" => [10, "Perception", "Willpower"],
    "Caldari Frigate" => [2, "Perception", "Willpower"],
    "Caldari Hauler" => [4, "Perception", "Willpower"],
    "Caldari Strategic Cruiser" => [5, "Perception", "Willpower"],
    "Caldari Tactical Destroyer" => [3, "Perception", "Willpower"],
    "Caldari Titan" => [16, "Perception", "Willpower"],
    "Capital Industrial Ships" => [12, "Perception", "Willpower"],
    "Capital Ships" => [14, "Perception", "Willpower"],
    "Command Destroyers" => [6, "Willpower", "Perception"],
    "Command Ships" => [8, "Willpower", "Perception"],
    "Covert Ops" => [4, "Willpower", "Perception"],
    "EDENCOM Battleship" => [8, "Perception", "Willpower"],
    "EDENCOM Cruiser" => [5, "Perception", "Willpower"],
    "EDENCOM Frigate" => [2, "Perception", "Willpower"],
    "Electronic Attack Ships" => [4, "Willpower", "Perception"],
    "Exhumers" => [5, "Willpower", "Perception"],
    "Expedition Frigates" => [4, "Willpower", "Perception"],
    "Flag Cruisers" => [8, "Willpower", "Perception"],
    "Gallente Battlecruiser" => [6, "Perception", "Willpower"],
    "Gallente Battleship" => [8, "Perception", "Willpower"],
    "Gallente Carrier" => [14, "Perception", "Willpower"],
    "Gallente Cruiser" => [5, "Perception", "Willpower"],
    "Gallente Destroyer" => [2, "Perception", "Willpower"],
    "Gallente Dreadnought" => [12, "Perception", "Willpower"],
    "Gallente Freighter" => [10, "Perception", "Willpower"],
    "Gallente Frigate" => [2, "Perception", "Willpower"],
    "Gallente Hauler" => [4, "Perception", "Willpower"],
    "Gallente Strategic Cruiser" => [5, "Perception", "Willpower"],
    "Gallente Tactical Destroyer" => [3, "Perception", "Willpower"],
    "Gallente Titan" => [16, "Perception", "Willpower"],
    "Heavy Assault Cruisers" => [6, "Willpower", "Perception"],
    "Heavy Interdiction Cruisers" => [6, "Willpower", "Perception"],
    "Industrial Command Ships" => [8, "Perception", "Willpower"],
    "Interceptors" => [4, "Willpower", "Perception"],
    "Interdictors" => [5, "Willpower", "Perception"],
    "Jump Freighters" => [14, "Willpower", "Perception"],
    "Lancer Dreadnoughts" => [16, "Perception", "Willpower"],
    "Logistics Cruisers" => [6, "Willpower", "Perception"],
    "Logistics Frigates" => [4, "Willpower", "Perception"],
    "Marauders" => [10, "Willpower", "Perception"],
    "Mining Barge" => [4, "Perception", "Willpower"],
    "Mining Frigate" => [2, "Perception", "Willpower"],
    "Minmatar Battlecruiser" => [6, "Perception", "Willpower"],
    "Minmatar Battleship" => [8, "Perception", "Willpower"],
    "Minmatar Carrier" => [14, "Perception", "Willpower"],
    "Minmatar Cruiser" => [5, "Perception", "Willpower"],
    "Minmatar Destroyer" => [2, "Perception", "Willpower"],
    "Minmatar Dreadnought" => [12, "Perception", "Willpower"],
    "Minmatar Freighter" => [10, "Perception", "Willpower"],
    "Minmatar Frigate" => [2, "Perception", "Willpower"],
    "Minmatar Hauler" => [4, "Perception", "Willpower"],
    "Minmatar Strategic Cruiser" => [5, "Perception", "Willpower"],
    "Minmatar Tactical Destroyer" => [3, "Perception", "Willpower"],
    "Minmatar Titan" => [16, "Perception", "Willpower"],
    "ORE Freighter" => [9, "Perception", "Willpower"],
    "ORE Hauler" => [4, "Perception", "Willpower"],
    "Precursor Battlecruiser" => [6, "Perception", "Willpower"],
    "Precursor Battleship" => [8, "Perception", "Willpower"],
    "Precursor Cruiser" => [5, "Perception", "Willpower"],
    "Precursor Destroyer" => [2, "Perception", "Willpower"],
    "Precursor Dreadnought" => [12, "Perception", "Willpower"],
    "Precursor Frigate" => [2, "Perception", "Willpower"],
    "Recon Ships" => [6, "Willpower", "Perception"],
    "Spaceship Command" => [1, "Perception", "Willpower"],
    "Transport Ships" => [6, "Willpower", "Perception"],
    "Upwell Freighter" => [10, "Perception", "Willpower"],
    "Upwell Hauler" => [4, "Perception", "Willpower"],
];
$skills_data += [
    "Anchoring" => [3, "Memory", "Charisma"],
    "Mercenary Den Management" => [8, "Memory", "Charisma"],
    "Starbase Defense Management" => [7, "Memory", "Charisma"],
    "Structure Doomsday Operation" => [2, "Memory", "Willpower"],
    "Structure Electronic Systems" => [2, "Memory", "Willpower"],
    "Structure Engineering Systems" => [2, "Memory", "Willpower"],
    "Structure Missile Systems" => [2, "Memory", "Willpower"],
];
$skills_data += [
    "Amarr Core Systems" => [1, "Intelligence", "Memory"],
    "Amarr Defensive Systems" => [1, "Intelligence", "Memory"],
    "Amarr Offensive Systems" => [1, "Perception", "Willpower"],
    "Amarr Propulsion Systems" => [1, "Perception", "Willpower"],
    "Caldari Core Systems" => [1, "Intelligence", "Memory"],
    "Caldari Defensive Systems" => [1, "Intelligence", "Memory"],
    "Caldari Offensive Systems" => [1, "Perception", "Willpower"],
    "Caldari Propulsion Systems" => [1, "Perception", "Willpower"],
    "Gallente Core Systems" => [1, "Intelligence", "Memory"],
    "Gallente Defensive Systems" => [1, "Intelligence", "Memory"],
    "Gallente Offensive Systems" => [1, "Perception", "Willpower"],
    "Gallente Propulsion Systems" => [1, "Perception", "Willpower"],
    "Minmatar Core Systems" => [1, "Intelligence", "Memory"],
    "Minmatar Defensive Systems" => [1, "Intelligence", "Memory"],
    "Minmatar Offensive Systems" => [1, "Perception", "Willpower"],
    "Minmatar Propulsion Systems" => [1, "Perception", "Willpower"],
];
$skills_data += [
    "Advanced Target Management" => [3, "Intelligence", "Memory"],
    "Gravimetric Sensor Compensation" => [2, "Intelligence", "Memory"],
    "Ladar Sensor Compensation" => [2, "Intelligence", "Memory"],
    "Long Range Targeting" => [2, "Intelligence", "Memory"],
    "Magnetometric Sensor Compensation" => [2, "Intelligence", "Memory"],
    "Radar Sensor Compensation" => [2, "Intelligence", "Memory"],
    "Signature Analysis" => [1, "Intelligence", "Memory"],
    "Target Management" => [1, "Intelligence", "Memory"],
];
$skills_data += [
    "Accounting" => [3, "Charisma", "Memory"],
    "Advanced Broker Relations" => [3, "Charisma", "Memory"],
    "Advanced Contracting" => [10, "Charisma", "Willpower"],
    "Broker Relations" => [2, "Willpower", "Charisma"],
    "Contracting" => [1, "Charisma", "Willpower"],
    "Corporation Contracting" => [3, "Charisma", "Willpower"],
    "Customs Code Expertise" => [2, "Charisma", "Memory"],
    "Daytrading" => [1, "Charisma", "Memory"],
    "Marketing" => [3, "Charisma", "Memory"],
    "Procurement" => [3, "Charisma", "Memory"],
    "Retail" => [2, "Willpower", "Charisma"],
    "Trade" => [1, "Willpower", "Charisma"],
    "Tycoon" => [6, "Charisma", "Memory"],
    "Visibility" => [3, "Charisma", "Memory"],
    "Wholesale" => [4, "Charisma", "Memory"],
];

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
            <label for="currentSP">Current Skill Points:</label>
            <input type="number" id="currentSP" value="0" min="0" style="width:150px;">
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
            if(targetSP <= currentSP) return {large: 0, small:0};
            let remainingSP = targetSP - currentSP;
            let largeCount = 0;
            let smallCount = 0;
            let tempSP = currentSP;

            while (remainingSP > 0) {
                let gainLarge = getLargeGain(tempSP);
                if(gainLarge <= 0) break;
                if(gainLarge > remainingSP) {
                    let gainSmall = getSmallGain(tempSP);
                    if(gainSmall > remainingSP) break;
                    else {
                        smallCount++;
                        tempSP += gainSmall;
                        remainingSP -= gainSmall;
                    }
                } else {
                    largeCount++;
                    tempSP += gainLarge;
                    remainingSP -= gainLarge;
                }
            }

            while (remainingSP > 0) {
                let gainSmall = getSmallGain(tempSP);
                if(gainSmall <= 0) break;
                smallCount++;
                tempSP += gainSmall;
                remainingSP -= gainSmall;
            }

            return {large: largeCount, small: smallCount};
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
            const currentSP = Number(document.getElementById('currentSP').value) || 0;

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
