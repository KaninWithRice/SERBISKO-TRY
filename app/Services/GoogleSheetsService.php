<?php

namespace App\Services;

class GoogleSheetsService
{
    public function getClient()
    {
        $client = new \Google\Client();
        $client->setAuthConfig(storage_path('app/google-credentials.json'));
        $client->addScope(\Google\Service\Sheets::SPREADSHEETS_READONLY);

        return $client;
    }

    public function getService()
    {
        return new \Google\Service\Sheets($this->getClient());
    }
}