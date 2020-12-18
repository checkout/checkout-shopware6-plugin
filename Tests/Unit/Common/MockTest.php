<?php

namespace Checkoutcom\Tests\Unit\Common;

class MockTest {
    
    public static function ckoContextResponse() {
        $response = '{
            "pk": "pk_test_154c33d2-0153-4fd3-94bf-564d4b1c347b",
            "amount": 156800,
            "currency": "EUR",
            "expiresAt": 1607755040,
            "reference": "AZ4C4AZVGiq3m4xUyUExi1ukZVW1BwGy",
            "description": "Token: AZ4C4AZVGiq3m4xUyUExi1ukZVW1BwGy",
            "customer": {
                "email": "test@checkout.com",
                "name": "Checkout Test"
            },
            "shipping": {
                "address": {
                    "addressLine1": "Rosengberg",
                    "city": "Ansgarstr",
                    "zip": "1234",
                    "country": "DE"
                }
            },
            "billing": {
                "address": {
                    "addressLine1": "Rosengberg",
                    "city": "Ansgarstr",
                    "zip": "1234",
                    "country": "DE"
                }
            },
            "products": [
                {
                    "name": "Aerodynamic Aluminum Pocket Marketing",
                    "quantity": 2,
                    "price": 78400
                }
            ],
            "createdOn": "2020-11-27T06:37:20.951Z",
            "apms": [
                {
                    "name": "klarna",
                    "schema": "https://cko-meia-sbox-data.s3-eu-west-1.amazonaws.com/apms/klarna.json",
                    "logoUrl": "https://cko-meia-sbox-data.s3-eu-west-1.amazonaws.com/apms/logos/klarna.svg",
                    "show": true,
                    "metadata": {
                        "details": {
                            "payment_method_category": [
                                {
                                    "identifier": "pay_over_time",
                                    "name": "Slice it.",
                                    "asset_urls": {
                                        "descriptive": "https://x.klarnacdn.net/payment-method/assets/badges/generic/klarna.svg",
                                        "standard": "https://x.klarnacdn.net/payment-method/assets/badges/generic/klarna.svg"
                                    }
                                },
                                {
                                    "identifier": "pay_later",
                                    "name": "Pay later.",
                                    "asset_urls": {
                                        "descriptive": "https://x.klarnacdn.net/payment-method/assets/badges/generic/klarna.svg",
                                        "standard": "https://x.klarnacdn.net/payment-method/assets/badges/generic/klarna.svg"
                                    }
                                }
                            ],
                            "session_id": "kcs_gh6nqhl6ja6ehilfonbq3p3rza",
                            "client_token": "eyJhbGciOiJSUzI1NiIsImtpZCI6IjgyMzA1ZWJjLWI4MTEtMzYzNy1hYTRjLTY2ZWNhMTg3NGYzZCJ9.ewogICJzZXNzaW9uX2lkIiA6ICJkM2E3YTU1Zi03ZGI5LTIzYTgtODhiOS1lMGUzZWFhODMxZGMiLAogICJiYXNlX3VybCIgOiAiaHR0cHM6Ly9rbGFybmEtcGF5bWVudHMtZXUucGxheWdyb3VuZC5rbGFybmEuY29tL3BheW1lbnRzIiwKICAiZGVzaWduIiA6ICJrbGFybmEiLAogICJsYW5ndWFnZSIgOiAiZW4iLAogICJwdXJjaGFzZV9jb3VudHJ5IiA6ICJERSIsCiAgInRyYWNlX2Zsb3ciIDogZmFsc2UsCiAgImVudmlyb25tZW50IiA6ICJwbGF5Z3JvdW5kIiwKICAibWVyY2hhbnRfbmFtZSIgOiAiUGxheWdyb3VuZCBEZW1vIE1lcmNoYW50IiwKICAic2Vzc2lvbl90eXBlIiA6ICJQQVlNRU5UUyIsCiAgImNsaWVudF9ldmVudF9iYXNlX3VybCIgOiAiaHR0cHM6Ly9ldS5wbGF5Z3JvdW5kLmtsYXJuYWV2dC5jb20iLAogICJleHBlcmltZW50cyIgOiBbIHsKICAgICJuYW1lIiA6ICJpbi1hcHAtc2RrLW5ldy1pbnRlcm5hbC1icm93c2VyIiwKICAgICJwYXJhbWV0ZXJzIiA6IHsKICAgICAgInZhcmlhdGVfaWQiIDogIm5ldy1pbnRlcm5hbC1icm93c2VyLWVuYWJsZSIKICAgIH0KICB9IF0KfQ.PQQb9vOL5KHcrpJdCALreDlThC7mJOCkQxQR-ICLkpC32FddqNX0plq-jg9HUtn2lAM6t-5fl9TRdEaEKHu5AtyYsuvTsBx5DoEHP13TKqFD3VPgx9NLUyanNgiMEPrKxd35N6LyU7IF7Cs_9RgAXrV9KKIcqTaTkLGrIxDIYoLFr1iDX9WqwNb0BIuOTIcE824nuZ_NZs15n9LeQuWdIMtACvmqgqEO0AaUManClhU4aRGhN6rQWw9UEta8Ufd1dQpoHsOUANlzEG_uMbi7nTEBythmXdVedE2P0MWumjhZYhIUqu-t6k3K6jdDwuHoPRX-RdFVzH4FLOr_4OUvcg"
                        },
                        "session": {
                            "purchase_country": "DE",
                            "purchase_currency": "EUR",
                            "locale": "de",
                            "order_amount": 156800,
                            "order_tax_amount": 0,
                            "order_lines": [
                                {
                                    "name": "Aerodynamic Aluminum Pocket Marketing",
                                    "quantity": 2,
                                    "tax_rate": 0,
                                    "total_amount": 156800,
                                    "total_discount_amount": 0,
                                    "total_tax_amount": 0,
                                    "unit_price": 78400
                                }
                            ],
                            "billing_address": {
                                "city": "Ansgarstr",
                                "country": "DE",
                                "email": "test@checkout.com",
                                "given_name": "Checkout",
                                "family_name": "Test",
                                "postal_code": "1234",
                                "street_address": "Rosengberg"
                            },
                            "shipping_address": {
                                "city": "Ansgarstr",
                                "country": "DE",
                                "email": "test@checkout.com",
                                "given_name": "Checkout",
                                "family_name": "Test",
                                "postal_code": "1234",
                                "street_address": "Rosengberg"
                            },
                            "customer": {}
                        }
                    }
                },
                {
                    "name": "sofort",
                    "schema": "https://cko-meia-sbox-data.s3-eu-west-1.amazonaws.com/apms/sofort.json",
                    "logoUrl": "https://cko-meia-sbox-data.s3-eu-west-1.amazonaws.com/apms/logos/sofort.svg",
                    "show": true
                },
                {
                    "name": "paypal",
                    "schema": "https://cko-meia-sbox-data.s3-eu-west-1.amazonaws.com/apms/paypal.json",
                    "logoUrl": "https://cko-meia-sbox-data.s3-eu-west-1.amazonaws.com/apms/logos/paypal.svg",
                    "show": true
                },
                {
                    "name": "sepa",
                    "schema": "https://cko-meia-sbox-data.s3-eu-west-1.amazonaws.com/apms/sepa.json",
                    "logoUrl": "https://cko-meia-sbox-data.s3-eu-west-1.amazonaws.com/apms/logos/sepa.svg",
                    "show": true,
                    "metadata": {
                        "creditor": {
                            "name": "b4payment GmbH",
                            "id": "DE36ZZZ00001690322",
                            "address": {
                                "zip": "93047",
                                "country": "DE",
                                "state": "Regensburg",
                                "address_line1": "ObermÃ¼nsterstraÃŸe 14",
                                "city": "Regensburg"
                            }
                        }
                    }
                },
                {
                    "name": "saveCard",
                    "schema": "https://cko-meia-sbox-data.s3-eu-west-1.amazonaws.com/apms/saveCard.json",
                    "logoUrl": "https://cko-meia-sbox-data.s3-eu-west-1.amazonaws.com/apms/logos/saveCard.svg",
                    "show": true
                }
            ],
            "id": "cid_b04ff1cd-c288-4af0-b889-1d1a85ae9231",
            "cvvRequired": false
        }';

        return $response;
    }

    public static function ckoContextNoApms() {
        $response = '{
            "pk": "pk_test_154c33d2-0153-4fd3-94bf-564d4b1c347b",
            "amount": 156800,
            "currency": "EUR",
            "expiresAt": 1607755040,
            "reference": "AZ4C4AZVGiq3m4xUyUExi1ukZVW1BwGy",
            "description": "Token: AZ4C4AZVGiq3m4xUyUExi1ukZVW1BwGy",
            "customer": {
                "email": "test@checkout.com",
                "name": "Checkout Test"
            },
            "shipping": {
                "address": {
                    "addressLine1": "Rosengberg",
                    "city": "Ansgarstr",
                    "zip": "1234",
                    "country": "DE"
                }
            },
            "billing": {
                "address": {
                    "addressLine1": "Rosengberg",
                    "city": "Ansgarstr",
                    "zip": "1234",
                    "country": "DE"
                }
            },
            "products": [
                {
                    "name": "Aerodynamic Aluminum Pocket Marketing",
                    "quantity": 2,
                    "price": 78400
                }
            ],
            "createdOn": "2020-11-27T06:37:20.951Z",
            "id": "cid_b04ff1cd-c288-4af0-b889-1d1a85ae9231",
            "cvvRequired": false
        }';

        return $response;
    }
}