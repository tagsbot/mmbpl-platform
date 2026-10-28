# MMBPL Website: cPanel par install karne ka tareeqa

Is package mein website, admin panel, mobile app ki API aur Koha sync API, sab ek hi PHP app mein hain. Database aap ke cPanel ka MySQL hoga, aur saare platforms (website, app, Sync Agent) isi se chalein ge.

## Zaroori cheezein
- cPanel hosting, PHP **8.1 ya naya** (8.2 / 8.3 best), aur MySQL / MariaDB
- Ek domain ya sub-domain (jaise `library.aapkadomain.com`)
- File: `MMBPL-Website-v1.0.0.zip`

## Step 1: Sub-domain banayein
1. cPanel → **Domains** (ya *Subdomains*) → naya sub-domain banayein, misaal `library`.
2. "Document Root" note kar lein, jaise `public_html/library`. Website isi folder mein jaye gi.

## Step 2: PHP version
cPanel → **Select PHP Version** (ya *MultiPHP Manager*) → sub-domain ke liye **PHP 8.2** select karein.
Extensions mein ye on hon: `pdo_mysql`, `mbstring`, `fileinfo`, `json`.

## Step 3: Database
1. cPanel → **MySQL® Databases**
2. Naya database banayein, misaal `cpuser_mmbpl`
3. Naya user banayein, misaal `cpuser_mmbpl` (mazboot password ke sath)
4. "Add User To Database" mein user ko database se jorein aur **ALL PRIVILEGES** dein
5. Teeno cheezein note kar lein: database name, user aur password

## Step 4: Files upload karein
1. cPanel → **File Manager** → sub-domain ka folder kholein (`public_html/library`)
2. **Upload** → `MMBPL-Website-v1.0.0.zip` upload karein
3. Zip par right-click → **Extract**
4. Check karein ke `index.php`, `.htaccess`, `app`, `assets`, `install` wagera isi folder mein hon, kisi aur sub-folder mein nahi.
   (`.htaccess` dekhne ke liye File Manager → Settings → "Show Hidden Files" on karein.)
5. `uploads` aur `app` folders ki permission **755** honi chahiye.

## Step 5: Installer chalayein
1. Browser mein kholein: `https://library.aapkadomain.com/install/`
2. Server check mein sab ✓ ho
3. Database ki details daalein (host aksar `localhost` hota hai)
4. Admin ka naam, email aur password (kam az kam 10 characters) daalein
5. **"Load demo data"** on rehne dein, taake Koha connect hone se pehle sab kuch test kar sakein
6. **Install now**

## Step 6: Security (zaroori)
- File Manager se **`install` folder delete** kar dein.
- cPanel → **SSL/TLS Status** → sub-domain ke liye AutoSSL chalayein (https).
- SSL chalne ke baad `.htaccess` mein "force HTTPS" wali do lines ke shuru se `#` hata dein.

## Step 7: Admin panel
- `https://library.aapkadomain.com/admin` par sign in karein.
- **Settings** mein library ka asli pata, phone, email, Facebook, timings, fees aur Koha category codes daalein.
- **Pages** mein "About", "Rules" aur "Privacy" ki asli tehreer daalein.
- **Gallery** mein library ki asli tasveerein daalein.
- **Events / News** mein pehli khabrein aur events daalein.

## Demo login (sirf testing ke liye)
- Member: card `MMB-004521`, password `demo12345`
- Jab Koha ka asli data aa jaye: Admin **Dashboard** → **Remove demo data**

## SMS (OTP aur reminders)
Pakistan ke kisi bhi SMS provider ka HTTP API link Admin → Settings → "SMS gateway URL" mein daalein, jaise:
`https://sms.provider.pk/api/send?user=XX&pass=XX&to={to}&text={text}`
Jab tak SMS set nahi hota, member card + mobile + date of birth se account activate kar sakte hain.

## Cron job (optional)
Koi cron zaroori nahi. Reminders aur notifications Sync Agent ke har sync par khud ban jaate hain.

## Update kaise karein
Nayi zip ki files upload karke overwrite karein. Lekin `app/config.php` aur `uploads/` folder ko **kabhi delete na karein**.

## Masla ho to
- **500 error**: PHP version 8.1+ check karein. `app/storage/error.log` dekhein.
- **Sirf home page khulta hai, baqi pages 404**: `.htaccess` upload nahi hua (Show Hidden Files on karke dobara check karein).
- **Upload fail**: cPanel → MultiPHP INI Editor → `upload_max_filesize = 10M`, `post_max_size = 12M`.
