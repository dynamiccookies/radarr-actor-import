<?php
// Load settings from file
$settingsFile = 'settings.ini.php';
$settings     = [];
if (file_exists($settingsFile)) {$settings = parse_ini_file($settingsFile);}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['saveSettings'])) {
        // Save settings
        $settings['radarrServer']          = $_POST['radarrServer'];
        $settings['radarrApiKey']          = $_POST['radarrApiKey'];
        $settings['tmdbApiKey']            = $_POST['tmdbApiKey'];
//      $settings['listNamePattern']       = $_POST['listNamePattern'];
        $settings['rootFolderPath']        = $_POST['rootFolderPath'];
        $settings['qualityProfile']        = $_POST['qualityProfile'];
        $settings['minAvailability']       = $_POST['minAvailability'];
        $settings['monitor']               = $_POST['monitor'];
        $settings['createTag']             = isset($_POST['createTag']);
        $settings['personCast']            = isset($_POST['personCast']);
        $settings['personDirectorCredits'] = isset($_POST['personDirectorCredits']);
        $settings['personProducerCredits'] = isset($_POST['personProducerCredits']);
        $settings['personSoundCredits']    = isset($_POST['personSoundCredits']);
        $settings['personWritingCredits']  = isset($_POST['personWritingCredits']);
        $settings['enableList']            = isset($_POST['enableList']);
        $settings['enableAutomaticAdd']    = isset($_POST['enableAutomaticAdd']);
        $settings['searchOnAdd']           = isset($_POST['searchOnAdd']);

        $settingsContent = '';
        foreach ($settings as $key => $value) {
            $settingsContent .= "$key = " . (is_bool($value) ? ($value ? 'true' : 'false') : $value) . "\n";
        }
        file_put_contents($settingsFile, $settingsContent);
        $settingsSavedMessage = '<p>Settings saved successfully.</p>';
    }

    if (!empty($_POST['actorName'])) {
        $actorName  = htmlspecialchars($_POST['actorName']);
        $tmdbApiKey = $settings['tmdbApiKey'];

        // Fetch actors from TMDB
        $tmdbUrl = "https://api.themoviedb.org/3/search/person?api_key={$tmdbApiKey}&query=" . urlencode($actorName);
        $tmdbResponse = file_get_contents($tmdbUrl);
        $tmdbData = json_decode($tmdbResponse, true);

        $searchResults = '';
        if (!empty($tmdbData['results'])) {
            $searchResults .= '<div class="grid-container">';
            foreach ($tmdbData['results'] as $actor) {
                $actorId = $actor['id'];
                $actorName = $actor['name'];
                $actorPhoto = !empty($actor['profile_path']) ? 'https://image.tmdb.org/t/p/w500' . $actor['profile_path'] : 'https://dummyimage.com/200x300/cccccc/000.png&text=No%20Image%20Available';

                $searchResults .= '<div class="grid-item">';
                $searchResults .= '<img src="' . $actorPhoto . '" alt="' . $actorName . '" class="actor-photo">';
                $searchResults .= '<p><a href="https://www.themoviedb.org/person/' . $actorId . '-' . str_replace(' ', '-', $actorName) . '" target="_blank">' . $actorName . '</a></p>';
                $searchResults .= '<form method="POST">';
                $searchResults .= '<input type="hidden" name="actorId" value="' . $actorId . '">';
                $searchResults .= '<button type="button" name="addToRadarr" onclick="createList(\'' . $actorId . '\',\'' . $actorName . '\')">Add to Radarr</button>';
                $searchResults .= '</form>';
                $searchResults .= '</div>';
            }
            $searchResults .= '</div>';
        } else {
            $searchResults = '<p>No actors found.</p>';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Actor Search and Radarr Import</title>
    <style>
        .grid-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
        }
        .grid-item {
            text-align: center;
            border: 1px solid #ccc;
            padding: 10px;
        }
        .actor-photo {
            width: 100%;
            height: auto;
        }
        .settings-section, #saveSettings {
            display: none;
        }
    </style>
    <script>
        function toggleSettings() {
            const settingsSection         = document.getElementById('settings-section');
            settingsSection.style.display = settingsSection.style.display != 'block' ? 'block' : 'none';
        }

        function toggleSubmitButton() {
            const saveSettingsCheckbox = document.getElementById('settings');
            const submitButton         = document.getElementById('saveSettings');
            submitButton.style.display = saveSettingsCheckbox.checked ? 'block' : 'none';
        }

        function fetchRootFolders() {
            const apiUrl = document.getElementById('radarrServer').value + '/api/v3/rootfolder';
            const apiKey = document.getElementById('radarrApiKey').value;
            const rootFolderSelect = document.getElementById('rootFolderPath');
            const rootFolderSaved = '<?= (isset($settings['rootFolderPath']) ? $settings['rootFolderPath'] : '');?>';

            fetch(apiUrl, {
                method: 'GET',
                headers: {
                    'X-Api-Key': apiKey
                }
            })
            .then(response => response.json())
            .then(data => {
                rootFolderSelect.innerHTML = ''; // Clear existing options

                data.forEach(folder => {
                    const option       = document.createElement('option');
                    option.value       = folder.path;
                    option.textContent = folder.path;

                    if (folder.path == rootFolderSaved) {option.selected = true;}

                    rootFolderSelect.appendChild(option);
                });
            })
            .catch(error => {
                console.error('Error fetching root folders:', error)
                rootFolderSelect.innerHTML = ''; // Clear existing options

            });
        }

        function fetchQualityProfiles() {
            const apiUrl = document.getElementById('radarrServer').value + '/api/v3/qualityprofile';
            const apiKey = document.getElementById('radarrApiKey').value;
            const qualityProfileSelect = document.getElementById('qualityProfile');
            const qualityProfileSaved = '<?= (isset($settings['qualityProfile']) ? $settings['qualityProfile'] : '');?>';

            fetch(apiUrl, {
                method: 'GET',
                headers: {
                    'X-Api-Key': apiKey
                }
            })
            .then(response => response.json())
            .then(data => {
                qualityProfileSelect.innerHTML = ''; // Clear existing options

                data.forEach(profile => {
                    const option       = document.createElement('option');
                    option.value       = profile.id;
                    option.textContent = profile.name;

                    if (profile.id == qualityProfileSaved) {option.selected = true;}

                    qualityProfileSelect.appendChild(option);
                });
            })
            .catch(error => {
                console.error('Error fetching quality profiles:', error)
                qualityProfileSelect.innerHTML = ''; // Clear existing options

            });
        }

        function fetchLists() {
            const apiUrl = document.getElementById('radarrServer').value + '/api/v3/importlist';
            const apiKey = document.getElementById('radarrApiKey').value;
            listIds = [];

            fetch(apiUrl, {
                method: 'GET',
                headers: {
                    'X-Api-Key': apiKey
                }
            })
            .then(response => response.json())
            .then(data => {
                data.forEach(lists => {
                    if (lists.fields[0].name == 'personId') {
                        listIds.push(lists.fields[0].value);
                    }
                });

                document.querySelectorAll('.grid-item').forEach(gridItem => {
                    const actorId = gridItem.querySelector('input[name="actorId"]').value;
                    const button  = gridItem.querySelector('button[name="addToRadarr"]');
                    const name    = gridItem.querySelector('a').textContent;

                    if (listIds.includes(actorId)) {
                        button.disabled = true;
                        gridItem.title  = name + '\'s list already exists'; // Set hover text on the grid-item
                    }
                });

            })
            .catch(error => {
                console.error('Error fetching quality profiles:', error)
            });
        }

        function createList(id, name) {
            const apiUrl = document.getElementById('radarrServer').value + '/api/v3/importlist';
            const apiKey = document.getElementById('radarrApiKey').value;

            createTag(name, apiKey).then(tag => {
                const postData = generatePostData(id, name, tag);

                fetch(apiUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Api-Key': apiKey
                    },
                    body: JSON.stringify(postData)
                })
                .then(response => response.json())
                .then(data => {
                    if (data) {
                        document.getElementById('radarrMessage').innerHTML = name + '\'s list added successfully!';
                    } else {
                        alert('Failed to add actor to Radarr.');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Failed to add actor to Radarr.');
                });

            });
        }

        async function createTag(actorName, apiKey) {
            const apiUrl        = document.getElementById('radarrServer').value + '/api/v3/tag';
            const convertedName = actorName.toLowerCase().replace(/ /g, '_');

            try {
                // Step 1: Fetch existing tags
                const response = await fetch(`${apiUrl}?apikey=${apiKey}`);
                const tags     = await response.json();

                // Step 2: Check if the tag already exists
                const existingTag = tags.find(tag => tag.label === convertedName);
                if (existingTag) {
                    return [existingTag.id];
                }

                // Step 3: If the tag doesn't exist, create a new one
                const newTagResponse = await fetch(`${apiUrl}?apikey=${apiKey}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ id: 0, label: convertedName })
                });

                if (!newTagResponse.ok) {
                    throw new Error('Failed to create new tag');
                }

                const newTag = await newTagResponse.json();

                // Step 4: Return the new tag's ID
                return [newTag.id];

            } catch (error) {
                console.error('Error handling actor tag:', error);
                return [];
            }
        }

        function generatePostData(id, name, tag) {
            return {
                "id": 0,
                "name": name,
                "fields": [
                    {
                        "order": 0,
                        "name": "personId",
                        "label": "PersonId",
                        "helpText": "TMDb Id of Person to Follow",
                        "value": id,
                        "type": "textbox",
                        "advanced": false,
                        "privacy": "normal",
                        "isFloat": false
                    },
                    {
                        "order": 1,
                        "name": "personCast",
                        "label": "Person Cast",
                        "helpText": "Select if you want to include Cast credits",
                        "value": isChecked('personCast'),
                        "type": "checkbox",
                        "advanced": false,
                        "privacy": "normal",
                        "isFloat": false
                    },
                    {
                        "order": 2,
                        "name": "personCastDirector",
                        "label": "Person Director Credits",
                        "helpText": "Select if you want to include Director credits",
                        "value": isChecked('personDirectorCredits'),
                        "type": "checkbox",
                        "advanced": false,
                        "privacy": "normal",
                        "isFloat": false
                    },
                    {
                        "order": 3,
                        "name": "personCastProducer",
                        "label": "Person Producer Credits",
                        "helpText": "Select if you want to include Producer credits",
                        "value": isChecked('personProducerCredits'),
                        "type": "checkbox",
                        "advanced": false,
                        "privacy": "normal",
                        "isFloat": false
                    },
                    {
                        "order": 4,
                        "name": "personCastSound",
                        "label": "Person Sound Credits",
                        "helpText": "Select if you want to include Sound credits",
                        "value": isChecked('personSoundCredits'),
                        "type": "checkbox",
                        "advanced": false,
                        "privacy": "normal",
                        "isFloat": false
                    },
                    {
                        "order": 5,
                        "name": "personCastWriting",
                        "label": "Person Writing Credits",
                        "helpText": "Select if you want to include Writing credits",
                        "value": isChecked('personWritingCredits'),
                        "type": "checkbox",
                        "advanced": false,
                        "privacy": "normal",
                        "isFloat": false
                    }
                ],
                "implementationName": "TMDb Person",
                "implementation": "TMDbPersonImport",
                "configContract": "TMDbPersonSettings",
                "infoLink": "https://wiki.servarr.com/radarr/supported#tmdbpersonimport",
                "tags": isChecked('createTag') ? tag : [],
                "enabled": isChecked('enableList'),
                "enableAuto": isChecked('enableAutomaticAdd'),
                "monitor": getSelectedValue('monitor'),
                "rootFolderPath": getSelectedValue('rootFolderPath'),
                "qualityProfileId": getSelectedValue('qualityProfile'),
                "searchOnAdd": isChecked('searchOnAdd'),
                "minimumAvailability": getSelectedValue('minAvailability'),
                "listType": "tmdb",
                "listOrder": 1,
                "minRefreshInterval": "12:00:00"
            };
        }

        // Function to get selected value from a select element
        function getSelectedValue(id) {
            return document.getElementById(id)?.value ?? '';
        }

        // Function to check if a checkbox is checked
        function isChecked(id) {
            return document.getElementById(id)?.checked ?? false;
        }

        window.onload = function() {
            fetchRootFolders();
            fetchQualityProfiles();
            fetchLists();
        };
    </script>
</head>
<body>
    <h1>Search Actor and Add to Radarr</h1>
    <button onclick="toggleSettings()">Toggle Settings</button>
    <div id="settings-section" class="settings-section">
        <h2>Settings</h2>
        <form method="POST">
            <label for="radarrServer">Radarr Server & Port:</label>
            <input type="text" id="radarrServer" name="radarrServer" value="<?= htmlspecialchars($settings['radarrServer'] ?? '') ?>" oninput="fetchRootFolders(); fetchQualityProfiles();">
            <br><br>
            <label for="radarrApiKey">Radarr API Key:</label>
            <input type="text" id="radarrApiKey" name="radarrApiKey" value="<?= htmlspecialchars($settings['radarrApiKey'] ?? '') ?>" oninput="fetchRootFolders(); fetchQualityProfiles();">
            <br><br>
            <label for="tmdbApiKey">TMDB API Key:</label>
            <input type="text" id="tmdbApiKey" name="tmdbApiKey" value="<?= htmlspecialchars($settings['tmdbApiKey'] ?? '') ?>">
<!-- Will look into adding this functionality in the future
            <br><br>
            <label for="listNamePattern">List Name Pattern:</label>
            <input type="text" id="listNamePattern" name="listNamePattern" value="<?= htmlspecialchars($settings['listNamePattern'] ?? '') ?>">
-->
            <br><br>
            <label for="rootFolderPath">Root Folder Path:</label>
            <select id="rootFolderPath" name="rootFolderPath">
                <!-- Root folder options will be populated by JavaScript -->
            </select>
            <br><br>
            <label for="qualityProfile">Quality Profile:</label>
            <select id="qualityProfile" name="qualityProfile">
                <!-- Quality profile options will be populated by JavaScript -->
            </select>
            <br><br>
            <label for="minAvailability">Minimum Availability:</label>
            <select id="minAvailability" name="minAvailability">
                <option value="announced"<?= isset($settings['minAvailability']) && $settings['minAvailability'] == 'announced' ? ' selected' : '' ?>>Announced</option>
                <option value="in_cinemas"<?= isset($settings['minAvailability']) && $settings['minAvailability'] == 'in_cinemas' ? ' selected' : '' ?>>In Cinemas</option>
                <option value="released"<?= isset($settings['minAvailability']) && $settings['minAvailability'] == 'released' ? ' selected' : '' ?>>Released</option>
            </select>
            <br><br>
            <label for="monitor">Monitor:</label>
            <select id="monitor" name="monitor">
                <option value="movieOnly" <?= isset($settings['monitor']) && $settings['monitor'] == 'movieOnly' ? 'selected' : '' ?>>Movie Only</option>
                <option value="movieAndCollection" <?= isset($settings['monitor']) && $settings['monitor'] == 'movieAndCollection' ? 'selected' : '' ?>>Movie and Collection</option>
                <option value="none" <?= isset($settings['monitor']) && $settings['monitor'] == 'none' ? 'selected' : '' ?>>None</option>
            </select>
            <br><br>
            <label for="enableList">Enable List:</label>
            <input type="checkbox" id="enableList" name="enableList" <?= !empty($settings['enableList']) ? 'checked' : '' ?>>
            <br><br>
            <label for="enableAutomaticAdd">Enable Automatic Add:</label>
            <input type="checkbox" id="enableAutomaticAdd" name="enableAutomaticAdd" <?= !empty($settings['enableAutomaticAdd']) ? 'checked' : '' ?>>
            <br><br>
            <label for="searchOnAdd">Search on Add:</label>
            <input type="checkbox" id="searchOnAdd" name="searchOnAdd" <?= !empty($settings['searchOnAdd']) ? 'checked' : '' ?>>
            <br><br>
            <label for="createTag">Create Tag:</label>
            <input type="checkbox" id="createTag" name="createTag" <?= !empty($settings['createTag']) ? 'checked' : '' ?>>
            <br><br>
            <label for="personCast">Person Cast:</label>
            <input type="checkbox" id="personCast" name="personCast" <?= !empty($settings['personCast']) ? 'checked' : '' ?>>
            <br><br>
            <label for="personDirectorCredits">Person Director Credits:</label>
            <input type="checkbox" id="personDirectorCredits" name="personDirectorCredits" <?= !empty($settings['personDirectorCredits']) ? 'checked' : '' ?>>
            <br><br>
            <label for="personProducerCredits">Person Producer Credits:</label>
            <input type="checkbox" id="personProducerCredits" name="personProducerCredits" <?= !empty($settings['personProducerCredits']) ? 'checked' : '' ?>>
            <br><br>
            <label for="personSoundCredits">Person Sound Credits:</label>
            <input type="checkbox" id="personSoundCredits" name="personSoundCredits" <?= !empty($settings['personSoundCredits']) ? 'checked' : '' ?>>
            <br><br>
            <label for="personWritingCredits">Person Writing Credits:</label>
            <input type="checkbox" id="personWritingCredits" name="personWritingCredits" <?= !empty($settings['personWritingCredits']) ? 'checked' : '' ?>>
            <br><br>
            <input type="checkbox" id="settings" name="settings" onchange="toggleSubmitButton()"> Save Settings
            <input type="submit" id="saveSettings" name="saveSettings" value="Save Settings">
        </form>
    </div>
    <?= isset($settingsSavedMessage) ? $settingsSavedMessage : '' ?>
    <br><br>
    <form method="POST">
        <label for="actorName">Actor Name:</label>
        <input type="text" id="actorName" name="actorName" required value="<?= htmlspecialchars($_POST['actorName'] ?? '') ?>">
        <button type="submit">Search</button> <div id='radarrMessage'></div>
    </form>

    <?= isset($searchResults) ? $searchResults : '' ?>
</body>
</html>
