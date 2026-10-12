<?php
// ====== Paycorp (DFCC) PayCentreWeb4 settings ======
define('PAYCORP_ENDPOINT',    'https://paycorp-dfcc.prod.aws.paycorp.lk/rest/service/proxy');
define('PAYCORP_AUTH_TOKEN',  '6a741fd5-43e3-41c7-8e49-0cc3ee7ca56a');
define('PAYCORP_HMAC_SECRET', 'Pa1SOGifRpKk6E8R');
define('PAYCORP_CLIENT_ID',   '10000158');     // Client ID 01 - LKR - 10000158
define('PAYCORP_CURRENCY',    'LKR');

// Return URL වලට පාවිච්චි වෙන base URL (අගට / නැතිව).
// ඔයාගේ project එකේ SITE_URL කලින් define කරලා නම් ඒකම ගන්නවා, නැත්නම් පල්ලෙහා default එක.
// WAMP local test: 'http://localhost/sipway-online-school'
if (!defined('PAYCORP_BASE_URL')) {
    define('PAYCORP_BASE_URL', defined('SITE_URL') ? rtrim(SITE_URL, '/') : 'http://localhost/sipway-online-school');
}

// ★ Test කරන කාලයට true. Live යද්දී අනිවාර්යයෙන්ම false කරන්න (error details user ට පේන නිසා).
if (!defined('PAYCORP_DEBUG')) define('PAYCORP_DEBUG', true);

// WAMP එකේ "SSL certificate problem" ආවොත්: cacert.pem download කරලා path එක මෙතන දාන්න
// උදා: 'C:/wamp64/cacert.pem'   (නැත්නම් '' ලෙස තියන්න)
   if (!defined('PAYCORP_CA_BUNDLE')) define('PAYCORP_CA_BUNDLE', 'C:/wamp64/cacert.pem');