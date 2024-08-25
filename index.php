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
                $searchResults .= '<button type="submit" name="addToRadarr">Add to Radarr</button>';
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
                    option.value       = profile.name;
                    option.textContent = profile.name;

                    if (profile.name == qualityProfileSaved) {option.selected = true;}
                    
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
                <option value="movie_only" <?= isset($settings['monitor']) && $settings['monitor'] == 'movie_only' ? 'selected' : '' ?>>Movie Only</option>
                <option value="movie_and_collection" <?= isset($settings['monitor']) && $settings['monitor'] == 'movie_and_collection' ? 'selected' : '' ?>>Movie and Collection</option>
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
        <button type="submit">Search</button> <?= isset($radarrMessage) ? $radarrMessage : '' ?>
    </form>

    <?= isset($searchResults) ? $searchResults : '' ?>
</body>
</html>
