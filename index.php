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
    </style>
</head>
<body>
    <h1>Search Actor and Add to Radarr</h1>
    <form method="POST">
        <label for="actorName">Actor Name:</label>
        <input type="text" id="actorName" name="actorName" required>
        <br><br>
        <label for="radarrApiUrl">Radarr API URL:</label>
        <input type="text" id="radarrApiUrl" name="radarrApiUrl" required>
        <br><br>
        <button type="submit">Search</button>
    </form>

    <?php
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['actorName']) && !empty($_POST['radarrApiUrl'])) {
        $actorName = htmlspecialchars($_POST['actorName']);
        $radarrApiUrl = htmlspecialchars($_POST['radarrApiUrl']);
        $tmdbApiKey = 'YOUR_TMDB_API_KEY';  // Replace with your TMDB API key

        // Fetch actors from TMDB
        $tmdbUrl = "https://api.themoviedb.org/3/search/person?api_key={$tmdbApiKey}&query=" . urlencode($actorName);
        $tmdbResponse = file_get_contents($tmdbUrl);
        $tmdbData = json_decode($tmdbResponse, true);

        if (!empty($tmdbData['results'])) {
            echo '<div class="grid-container">';
            foreach ($tmdbData['results'] as $actor) {
                $actorId = $actor['id'];
                $actorName = $actor['name'];
                $actorPhoto = !empty($actor['profile_path']) ? 'https://image.tmdb.org/t/p/w500' . $actor['profile_path'] : 'https://via.placeholder.com/200';

                echo '<div class="grid-item">';
                echo '<img src="' . $actorPhoto . '" alt="' . $actorName . '" class="actor-photo">';
                echo '<p>' . $actorName . '</p>';
                echo '<form method="POST">';
                echo '<input type="hidden" name="radarrApiUrl" value="' . $radarrApiUrl . '">';
                echo '<input type="hidden" name="actorId" value="' . $actorId . '">';
                echo '<button type="submit" name="addToRadarr">Add to Radarr</button>';
                echo '</form>';
                echo '</div>';
            }
            echo '</div>';
        } else {
            echo '<p>No actors found.</p>';
        }
    }

    if (isset($_POST['addToRadarr']) && !empty($_POST['actorId']) && !empty($_POST['radarrApiUrl'])) {
        $actorId = htmlspecialchars($_POST['actorId']);
        $radarrApiUrl = htmlspecialchars($_POST['radarrApiUrl']);

        // Add actor to Radarr
        $radarrApiKey = 'YOUR_RADARR_API_KEY';  // Replace with your Radarr API key
        $radarrEndpoint = $radarrApiUrl . '/api/v3/command';

        $postData = json_encode([
            'name' => 'RefreshMovie',
            'personIds' => [$actorId]
        ]);

        $ch = curl_init($radarrEndpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'X-Api-Key: ' . $radarrApiKey
        ]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);

        $response = curl_exec($ch);
        curl_close($ch);

        if ($response) {
            echo '<p>Actor added to Radarr successfully.</p>';
        } else {
            echo '<p>Failed to add actor to Radarr.</p>';
        }
    }
    ?>
</body>
</html>
