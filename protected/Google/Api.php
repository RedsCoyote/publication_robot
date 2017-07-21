<?php

namespace App\Google;

use Google_Client;
use Google_Service_Sheets;

class Api
{
    const APPLICATION_NAME = 'Publication Robot';
    const CREDENTIALS_FILE = __DIR__ . '/../.credentials/sheets.googleapis.com.json';
    const CLIENT_SECRET_FILE = __DIR__ . '/../.credentials/client_secret.json';
    /**
     * Returns an authorized API service.
     * @return Google_Service_Sheets
     */
    public function getService()
    {
        $client = new Google_Client();
        $client->setApplicationName(self::APPLICATION_NAME);
        $client->setScopes([Google_Service_Sheets::SPREADSHEETS_READONLY]);
        $client->setAuthConfig(self::CLIENT_SECRET_FILE);
        $client->setAccessType('offline');
        // Load previously authorized credentials from a file.
        $credentialsPath = self::CREDENTIALS_FILE;
        if (file_exists($credentialsPath)) {
            $accessToken = json_decode(file_get_contents($credentialsPath), true);
        } else {
            // Request authorization from the user.
            $authUrl = $client->createAuthUrl();
            printf("Open the following link in your browser:\n%s\n", $authUrl);
            print 'Enter verification code: ';
            $authCode = trim(fgets(STDIN));
            // Exchange authorization code for an access token.
            $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);
            // Store the credentials to disk.
            if (!file_exists(dirname($credentialsPath))) {
                mkdir(dirname($credentialsPath), 0700, true);
            }
            file_put_contents($credentialsPath, json_encode($accessToken));
            printf("Credentials saved to %s\n", $credentialsPath);
        }
        $client->setAccessToken($accessToken);
        // Refresh the token if it's expired.
        if ($client->isAccessTokenExpired()) {
            $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
            file_put_contents($credentialsPath, json_encode($client->getAccessToken()));
        }
        return new Google_Service_Sheets($client);
    }
}
