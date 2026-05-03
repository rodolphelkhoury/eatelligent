#include <ESP8266WiFi.h>
#include <ESP8266HTTPClient.h>
#include <WiFiClientSecure.h>
#include <Wire.h>
#include <DFRobot_PN532.h>

const char* ssid       = "DSL_EXT";
const char* password   = "1122334455@@";
const char* backendUrl = "https://dbsm5jprkr.sharedwithexpose.com/api/nfc";
const char* apiKey     = "4c9f2b7bd0cbad049fa27e67b157d39c470126dc2400493b8db56b7bc5acd301";

#define PN532_IRQ   2
#define INTERRUPT   0

DFRobot_PN532_IIC nfc(PN532_IRQ, INTERRUPT);
DFRobot_PN532::sCard_t NFCcard;

void setup() {
  Serial.begin(9600);
  delay(1000);
  Wire.begin(4, 5);

  Serial.println("Starting NFC...");
  while (!nfc.begin()) {
    Serial.println("Didn't find PN532, retrying...");
    delay(1000);
  }
  Serial.println("Found PN532!");

  Serial.println("Connecting to WiFi...");
  WiFi.begin(ssid, password);
  while (WiFi.status() != WL_CONNECTED) {
    delay(500);
    Serial.print(".");
  }
  Serial.println("\nConnected! IP: ");
  Serial.println(WiFi.localIP());
}

void loop() {
  if (nfc.scan()) {
    NFCcard = nfc.getInformation();

    String uidStr = "";
    for (int i = 0; i < NFCcard.uidlenght; i++) {
      if (i > 0) uidStr += ":";
      if (NFCcard.uid[i] < 0x10) uidStr += "0";
      uidStr += String(NFCcard.uid[i], HEX);
    }
    uidStr.toUpperCase();

    Serial.println("Card UID: " + uidStr);

    if (WiFi.status() == WL_CONNECTED) {
      WiFiClientSecure client;
      client.setInsecure();

      HTTPClient http;
      http.begin(client, backendUrl);
      http.addHeader("Content-Type", "application/json");
      http.addHeader("Accept", "application/json");
      http.addHeader("esp_key", apiKey);

      String payload = "{\"uid\": \"" + uidStr + "\"}";
      int httpCode = http.POST(payload);

      Serial.print("HTTP Code: ");
      Serial.println(httpCode);

      if (httpCode > 0) {
        Serial.println("Response: " + http.getString());
      } else {
        Serial.println("POST failed: " + http.errorToString(httpCode));
      }
      http.end();
    }

    delay(2000);
  }
}