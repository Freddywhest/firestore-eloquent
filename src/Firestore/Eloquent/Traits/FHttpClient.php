<?php

namespace Roddy\FirestoreEloquent\Firestore\Eloquent\Traits;

use Illuminate\Support\Facades\Http;

trait FHttpClient
{
  private $FIRESTORE_URL = 'https://firestore.googleapis.com/v1/projects/';
  private $FIRESTORE_URL_DATABASE = '/databases/(default)/documents';

  private function getRequest(string $document, bool $use_prefix = true, $use_parser = true, string|int|null $id = null)
  {
    try {
      $url = $this->FIRESTORE_URL . $this->FIREBASE_PROJECT_ID . $this->FIRESTORE_URL_DATABASE . ($use_prefix ? '/' : '') . $document . ($id ? '/' . $id : '');
      $req = Http::get($url);
      if ($req->status() === 404) {
        return collect([]);
      }

      if ($req->failed()) {
        throw new \Exception($req->body());
      }
      return $use_parser ? collect($this->parseFirestoreJson($id ? ["documents" => [$req->json()]] : $req->json())) : collect($req->json());
    } catch (\Throwable $th) {
      throw $th;
    }
  }

  private function postRequest(string $document, array|object $data, bool $use_prefix = true, bool $use_parser = true, bool $is_paginate = false, string $param = null)
  {
    try {
      $array = explode("/", $this->collection);
      $doc = null;

      if (count($array) > 1 && !str_contains($document, '/')) {
        array_pop($array);
        $doc = "/" . implode("/", $array);
      }

      $url = $this->FIRESTORE_URL . $this->FIREBASE_PROJECT_ID .
        $this->FIRESTORE_URL_DATABASE .
        (is_null($doc) ? '' : $doc) .
        ($use_prefix ? '/' : '') .
        $document .
        ($param ? $param : '');

      $req = Http::post($url, $data);
      if ($req->status() === 404) {
        return collect([]);
      }

      if ($req->failed()) {
        throw new \Exception($req->body());
      }
      $jsonData = $req->json(); // Store in a variable first

      if (!empty($jsonData) && isset($jsonData[0]["skippedResults"])) {
        array_shift($jsonData); // Remove the first element
      }

      $dataReq = $jsonData;

      return $use_parser ? collect($this->parseFirestoreJson($dataReq)) : collect($req->json());
    } catch (\Throwable $th) {
      throw $th;
    }
  }

  private function patchRequest(string $document, array|object $data, bool $use_prefix = true, bool $use_parser = true, string $id = null)
  {
    try {
      $url = $this->FIRESTORE_URL . $this->FIREBASE_PROJECT_ID .
        $this->FIRESTORE_URL_DATABASE .
        ($use_prefix ? '/' : '') .
        $document . '/' . $id;

      $req = Http::patch($url, $data);

      if ($req->status() === 404) {
        return collect([]);
      }

      if ($req->failed()) {
        throw new \Exception($req->body());
      }
      $jsonData = $req->json(); // Store in a variable first

      if (!empty($jsonData) && isset($jsonData[0]["skippedResults"])) {
        array_shift($jsonData); // Remove the first element
      }

      $dataReq = $jsonData;

      return $use_parser ? collect($this->parseFirestoreJson($dataReq)) : collect($req->json());
    } catch (\Throwable $th) {
      throw $th;
    }
  }

  private function deleteRequest(string $document, bool $use_prefix = true, bool $use_parser = true, string $id = null)
  {
    try {
      $url = $this->FIRESTORE_URL . $this->FIREBASE_PROJECT_ID . $this->FIRESTORE_URL_DATABASE . ($use_prefix ? '/' : '') . $document . '/' . $id;
      $req = Http::delete($url);
      if ($req->failed()) {
        throw new \Exception($req->body());
      }
      $jsonData = $req->json(); // Store in a variable first

      if (!empty($jsonData) && isset($jsonData[0]["skippedResults"])) {
        array_shift($jsonData); // Remove the first element
      }

      $dataReq = $jsonData;

      return $use_parser ? collect($this->parseFirestoreJson($dataReq)) : collect($req->json());
    } catch (\Throwable $th) {
      throw $th;
    }
  }
}
