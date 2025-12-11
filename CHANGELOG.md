# **Tesseract LASAGNA changelog**

---

`2025-12-10`

* **`1091-BUGFIX`** updated **CakePHP Cache Module** to v5  
* **`1090-BUGFIX` patching system** for fixing dead vendor libraries  
* **`1089-BUGFIX`** updated **Paragonie Halite** to v5 (should fix 500 errors)

`2025-11-01`  **`v2.4.9`**

* **`1088-FEATURE`** support for **admin tabs** selective turn off (at least one tab must stay active)  
* **`1087-BUGFIX` support for \*** in shortcode masks like **\[gallery some\*thing\]**, new **tokenizer/detokenizer** of shortcodes

`2025-09-09`

* **`1086-BUGFIX` multi-tab logout** via Broadcast Channel

`2025-09-01`  **`v2.4.8`**

* **`1085-BUGFIX` bugfixes and UI enhancements**

`2025-08-25`

* **`1084-BUGFIX` regression fix** in randomized galleries \+ global switch

`2025-08-22`

* **`1083-FEATURE`** new **StringFilter:**  
  * **`[galleryspan <mask> [<random|order>]]`**  
* **`1082-FEATURE`** better **PIN switch colors**  
* **`1081-FEATURE`** new **MODEL-X expansion button**  
* **`1080-FEATURE`** custom **progress bar parameters**:  
  * `design.progress_bar_color: "#48f"`  
  * `design.progress_bar_size: "8px"`  
  * `design.progress_bar_background: "#ccc"`  
  * `design.progress_bar_transition: “width 0.3s ease-in-out”`

`2025-08-14`

* **`1079-FEATURE` CI tester** updated

`2025-08-13`

* **`1078-FEATURE` 80px XS thumbnails** (new uploads only)  
* **`1077-BUGFIX` bugfixes and UI enhancements**  
  * Halite cookie crash handler  
  * hardened file uploader

`2025-08-12`

* **`1076-FEATURE`** “**custom replacements**” from a Sheet data cell  
  * *`usr.custom_replacements`*  
* **`1075-FEATURE`** new **StringFilter parameters:**  
  * **`[gallery <mask> [<random|order>]]`**  
  * sorting parameter, optional, overrides program defaults  
* **`1074-BUGFIX` bugfixes and UI enhancements**

`2025-08-09`  **`v2.4.7`**  
**`1073-BUGFIX` bugfixes and UI enhancements**

`2025-08-07`

* **`1072-FEATURE`** new admin panel **DEV tab**

`2025-08-06`

* **`1071-BUGFIX` security enhancements** incl. browser fingerprinting

`2025-08-01`

* **`1070-FEATURE`** upgrade to **FontAwesome 7.0.0**

`2025-07-31`

* **`1069-BUGFIX` bugfixes and UI enhancements**

`2025-07-28`

* **`1068-FEATURE`** new **StringFilter** method:  
  * *`SF::convertEolToSpace()`*

`2025-07-25`

* **`1067-FEATURE`** virtual **/admin endpoint**  
  * Model key: **`admin`**  
  * default: **admin**  
  * max. length: **32 chars**  
  * can be set to any random string

`2025-07-22`

* **`1066-BUGFIX` bugfixes and UI enhancements**

`2025-07-17`

* **`1065-FEATURE` support** for Sheet data cells to alter the Model \- **MODEL-X:**  
  * **`cfg.key`** \- modify the Model key if the original exists  
  * **`usr.key`** \- set the Model key  
  * **`add.key`** \- add a string or merge arrays if the original key exists  
  * **`del.key`** \- delete the Model key

`2025-07-16`

* **`1064-BUGFIX`** added **Tracy configuration**:  
  * Debugger::$maxItems \= (int) ($cfg\['DEBUG\_MAX\_ITEMS'\] ?? 1000);

`2025-07-15`

* **`1063-BUGFIX`** added **CLI Signal handling** (Ctrl+C)

`2025-07-11`  **`v2.4.6`**

* **`1062-FEATURE` UI cleanup**, AuditLog filtered to separate **AUDIT and BLOCK logs**, some **extra SF replacements**, **TECHNICAL DETAILS** available in the UI, bug fixing…

`2025-07-08`

* **`1061-FEATURE`** documents updated: **README**, **TECHNICAL\_DETAILS\_EN**  
* **`1060-FEATURE`** new **StringFilters:**  
  * **`[mastodon id]`** \- Mastodon post (domain must be added to CSP\!)  
  * **`[twitch id]`** \- Twitch channell  
  * **`[twitchvid id]`** \- Twitch video  
  * **`[vimeo id]`** \- Vimeo video

`2025-06-23`

* **`1059-FEATURE` social icons pack** via “**custom replacements**” *(optional)*

`2025-06-22`

* **`1058-FEATURE`** new short code  
  * **`[figure image description]`** \- figure with a description

`2025-06-14`

* **`1057-FEATURE` .jpeg files automatically renamed** to .jpg on upload

`2025-05-24`

* **`1056-FEATURE` sharing button for Bluesky**  
  * separate **disable switches** for all sharing buttons  
  * *\#disable\_share\_bluesky: true*  
  * *\#disable\_share\_facebook: true*  
  * *\#disable\_share\_mastodon: true*  
* **`1055-BUGFIX` bot definitions** updated

`2025-05-12`

* **`1054-FEATURE` AuditLog UI** has 1000 visible entries or listing goes 150 days into the past  
  *    const MAX\_LOG\_ENTRIES \= 1000;  
  *    const VISIBLE\_LOG\_DAYS \= 5\*31;  
* **`1053-BUGFIX` removed console warnings** in Materialize framework at our CDN

`2025-05-11`

* **`1052-BUGFIX` logging messages** updated  
* **`1051-BUGFIX` bot definitions** updated  
* **`1050-FEATURE` changed logic** in administration for **displaying thumbnails** to allow caching

`2025-03-15`

* **`1049-FEATURE` hardened security and error logging**  
  * login possible at **/login** on the main domain  
  * checks if the user is already logged in

`2025-03-04`

* **`1048-FEATURE` hardened security and error logging**

`2025-02-21`  **`v2.4.5`**

* **`1047-FEATURE` enhanced offline/error pages**

`2025-02-19`

* **`1046-FEATURE` no more META keywords**, feature removed (Google just ignores it)  
* **`1045-FEATURE`** login workflow uses parameter **returnURL**  
* **`1044-FEATURE` StringFilter refactorization**

`2025-02-17`

* **`1043-FEATURE < 1009-IDEA`** **remember active admin tab**  
* **`1042-FEATURE`** new **StringFilter methods**:  
  * *`SF::shortCodesProcessor()`* \- process all shortcodes  
    * excluding `[googlemap]` as it needs API key  
  * *`SF::sort()`* \- sort array containing both numeric and textual values  
  * *`SF::rsort()`* \- sort array containing both numeric and textual values, reversed  
* **`1041-FEATURE`** **translations update**

`2025-02-12`

* **`1040-FEATURE`** **remote function roles**: admin, manager, editor  
  * **Admins** can: rebuild admin secure key, see the Audit Log  
  * **Managers** can: rebuild cookie secure key, rebuild authentication nonce  
  * **Editors** can: refresh CSV data, flush caches  
* **`1039-FEATURE`** **complete blocking of the robots** (optional)  
* **`1038-FEATURE`** **admin panel enhancements**  
* **`1037-FEATURE`** **robots definition** and **robots.txt template**

`2025-02-09`

* **`1036-FEATURE`** **recurrences in the Audit Log** expandable with a single click, **links to check IP addresses**, **filtering rows by type** with a double click

`2025-02-08`

* **`1035-FEATURE`** **admin/manager roles differentiated** (managers cannot access **Audit Log**)

`2025-02-07`

* **`1034-FEATURE`** **StringFilter** shortcode  
  * **`[googlemap <location>]`**  
  * location should be a “*plus glued string*”, e.g. `prague+castle+czechia`  
  * *SF::renderGoogleMapShortCode()*  
* **`1033-BUGFIX`** improved **StringFilters** input validation and **replacement pairs**  
* **`1032-FEATURE`** **admin panel enhancements**

`2025-02-03`

* **`1031-FEATURE`** **robots definition** and **robots.txt template**  
* **`1030-BUGFIX`** **improved automatic rate limiting** and **ban handling** (new)

`2025-01-14`

* **`1029-IDEA`** **complete rewrite of Lightbox2 plugin**  
* **`1028-FEATURE`** **admin panel enhancements**  
* **`1027-FEATURE < 1007-IDEA` add visual labels** for latest files uploaded: 🟡

`2025-01-13`

* **`1026-FEATURE`** support for **external link** CSS icons  
* **`1025-BUGFIX`** **reverting back to jQuery v3.7.1, 1022-BUGFIX was a failure**

`2025-01-11`  **`v2.4.4`**

* **`1024-FEATURE`** **admin panel enhancements**  
* **`1023-BUGFIX`** switching from **jQuery.parseJSON()** to **JSON.parse()**

`2025-01-10`

* ~~**`1022-BUGFIX`** testing **jQuery** [v4.0.0 beta](https://blog.jquery.com/2024/02/06/jquery-4-0-0-beta/)~~

`2025-01-09`

* **`1021-FEATURE`** **right-click** and **long-press** touch devices event handling in JavaScript  
* **`1020-BUGFIX`** **masking email addresses** in the admin panel

`2025-01-08`

* **`1019-FEATURE`** **admin** logout/Auditlog **block** is sticky to the right top  
* **`1018-BUGFIX`** **constants** hardening in **Bootstrap.php**

`2025-01-07`

* **`1017-BUGFIX`** **admin logout** goes to the main site

`2025-01-06`

* **`1016-FEATURE`** **admin panel enhancements**  
* **`1015-BUGFIX`** **file manager** counts “`displayed / total`”  
* **`1014-BUGFIX`** **natural sort order** for generated image galleries

`2025-01-05`

* **`1013-FEATURE < 1006-IDEA` total uploads size \+ file counts** @ admin panel, separate **info about generated thumbnails**

`2025-01-04`

* **`1012-BUGFIX`** migrate **Materialize framework** to [v1.2.2](https://github.com/materializecss/materialize/releases/tag/1.2.2)  
* **`1011-FEATURE`** [changelog](https://github.com/GSCloud/lasagna/blob/master/CHANGELOG.md) added to the admin panel  
* **`1010-BUGFIX`** **Lightbox2** [v2.11.5](https://github.com/lokesh/lightbox2/releases/tag/v2.11.5)  
* ~~**`1009-IDEA`** **remember active admin tab**~~  
* **`1008-IDEA` migrate Materialize framework to the newest [v2.2.2](https://github.com/materializecss/materialize/releases/tag/v2.2.2)**  
  * note: [https://github.com/materializecss/materialize/pull/49](https://github.com/materializecss/materialize/pull/49)

`2025-01-02`

* ~~**`1007-IDEA`** **add visual labels** for latest files uploaded~~  
* ~~**`1006-IDEA`** show **total uploads sizes** \+ counts in admin panel~~

`2024-12-28`

* **`1005-BUGFIX`** **regression** in a new CDN hash after global cache flush  
* **`1004-FEATURE`** **StringFilters** replacements update

`2024-12-27`

* **`1003-BUGFIX`** **admin panel shows core timings**

`2024-12-26`

* **`1002-BUGFIX`** fixed **thumbnails creation in WebP** format

`2024-12-23`

* **`1001-FEATURE`** **changelog started** (the best idea ever)

