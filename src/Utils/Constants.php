<?php

namespace Core\Utils;


class Constants
{
    const REGEXINT = '[0-9]+';

    const mobileNetworkEnum = ['MTN', 'MOOV'];
    const REGEXUUID = '^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$';
    const qosMtnPaymentRequestEndpoint = "https://qosic.net:8443/QosicBridge/user/requestpayment";
    const qosMtnSendMoneyEndpoint = "https://qosic.net:8443/QosicBridge/user/deposit";

    const qosMoovPaymentRequestEndpoint = "https://qosic.net:8443/QosicBridge/user/requestpaymentmv";
    const qosMoovSendMoneyEndpoint = "https://qosic.net:8443/QosicBridge/user/depositmv";

    const qosGetTransactionOrPaymentStatusEndpoint = "https://qosic.net:8443/QosicBridge/user/gettransactionstatus2";
    const user = "QSUSR32";
    const password = "Y91K2OLQ5SQUY45AK457XB1V37";
    const mtnClientId = "YLOMI789";
    const moovClientId = "YLOMI7MV";
    const quosCardKey = "QCBJ163";
    const qosCardPaymentRequestEndpoint =  "https://b-card.qosic.net/public/v1/initTransaction";

    const qosGetCardTransactionOrPaymentStatusEndpoint = "https://api.qosic.net/QosicBridge/checkout/v1/status";

    const url = "https://route.kyasms.net/api/v2/sms/send";
    const apiKey   = 'P54zL0l9IIvwH9znzCBDxrE0pVXQZCf0';
    const clientId = "79";

    const pushNotificationUrl = "https://onesignal.com/api/v1/notifications";
    const pushNotificationAppId = "2d765ff8-af7b-45af-a691-d58ff96e7181";
    const pushNotificationAuthKey = "OWI4Zjg0YTEtYjJiYy00MjBjLTk3MjYtZWYwM2Y1YWUzYTBm";


    const API_LINKEDIN_CLIENT_KEY = "";

    const API_LINKEDIN_CLIENT_ID = "";
    const API_LINKEDIN_TOKEN = "AQXTu-jE5EOi3e7_qrVF_jBrWnfIdGgzJG88dNIhV6hiH-BS-kfmEfL3AQy07ZYHQdcedrQunuUyMn6Ko49t8LljuEsib8WLYLtvleVDVgAKR4UAOd5NLZRQAyiKxqmctGHESY77Fs209ELuy5h1U5dxbsmC0SlztsIvGyxm437sv_udE5O-yqWdhCEFFlBL9DgPrDRCEYMEBNNvqiAXiYtWu-GRYhw7xSJwFAVBNWAs3mmV7GcbTvr8FL2X0aN6XDp_aBPjVS4vIiAb1tNC9cymK30VzCPgCOfNZzRMiCdW1y4dkCW-ihJom5o1A6ndOVIkopmS3JXtT9di1ca-ayDj1v8TyA";

    const API_LINKEDIN_PERSON_URN = "urn:li:person:tzNeRXHFE1";


    const FACEBOOK_APP_ID="your_app_id";
    const FACEBOOK_APP_SECRET="your_app_secret";
    const FACEBOOK_ACCESS_TOKEN="your_access_token";

}
