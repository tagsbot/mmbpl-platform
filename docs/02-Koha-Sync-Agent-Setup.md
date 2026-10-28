# Koha + Sync Agent setup (library ke computer par)

Koha library ke PC par chalta hai aur internet se bahar nazar nahi aata. Is liye **MMBPL Sync Agent** usi PC (ya usi network ke kisi PC) par chalaya jata hai. Ye:
- har 5 minute baad Koha se kitabein, copies, members, jaari kitabein aur bookings parh kar website ke database mein bhejta hai,
- har 30 second baad website / app se aaye renew, reserve, cancel aur nayi membership ke requests Koha mein khud kar deta hai.

Koha ka database kabhi direct touch nahi hota. Sirf Koha ke apne reports aur REST API istemal hote hain.

---

## Hissa A: Koha mein ek dafa ki tayyari (librarian / admin)

### A1. API user banayein
1. Koha staff → **Patrons → New patron** → category *Staff*
2. Naam: `MMBPL API`, username: `mmbpl_api`, mazboot password
3. Save ke baad **More → Set permissions** mein ye on karein:
   - `catalogue` (staff interface)
   - `borrowers` (add/modify patrons)
   - `circulate` (circulate_remaining_permissions)
   - `reserveforothers` (place holds)
   - `reports` → `execute_reports`

### A2. REST API on karein
Administration → **System preferences** mein search karein aur Save karein:
- `RESTBasicAuth` = **Enable**
- (optional) `RESTOAuth2ClientCredentials` = Enable. Phir user ki API key ka client ID / secret agent mein daal sakte hain.

### A3. 5 reports banayein
1. Koha → **Reports → Create from SQL**
2. Folder mein `koha-reports.sql` file kholein (Notepad se). Is mein 5 reports hain.
3. Har report ka SQL copy karke naya report banayein. Naam file mein likha hai (jaise "MMBPL sync 1: biblios").
4. Save karne ke baad report ka **number (id)** note karein. Ye report list mein ya address bar mein `id=` ke baad likha hota hai.
5. Reports ko **public na** banayein.

### A4. Library code
Administration → **Libraries** se apni library ka **code** note karein (jaise `MMBPL`).
Administration → **Patron categories** se Student / General / Child ke **codes** note karein, aur website Admin → Settings mein "Koha category code" ke khanon mein daalein.

---

## Hissa B: Website par agent key
1. Website Admin → **Koha sync** → **Create agent key**
2. Key copy kar lein. Ye sirf ek baar dikhti hai.
3. "Website address for the agent" bhi note kar lein.

---

## Hissa C: Library ke PC par Sync Agent
1. `MMBPL-Sync-Agent-Windows.zip` ko kisi pakki jagah unzip karein, jaise `C:\MMBPL\Sync-Agent\`
2. **`1-Start-Agent-Setup.bat`** par double-click karein. Browser mein settings page khul jaye ga (`http://127.0.0.1:8765`).
3. Settings bharein:
   - Website address + agent key (Hissa B se)
   - Koha staff address: wahi jo staff browser mein kholte hain (jaise `http://localhost:8080` ya `http://192.168.1.10:8080`)
   - Koha user `mmbpl_api` aur us ka password
   - Library code
   - 5 report numbers
4. **Save settings**, phir **Test connection**. Sab ✓ hone chahiye.
5. **Full sync** dabayein. Pehli dafa kuch minute lag sakte hain.
6. Website Admin → Catalog / Members mein data nazar aane lagay ga.
7. Setup wali black window band kar dein, phir **`2-Install-Autostart.bat`** par right-click → **Run as administrator**. Ab agent har dafa computer on hone par khud chupke se chalta rahe ga.

Settings dobara kholne ke liye `Open-Settings.url` par click karein.
Band karne ke liye `3-Remove-Autostart.bat` (as administrator) chalayein.

---

## Kya kya sync hota hai

| Koha se website | Har 5 min | Raat ko full sync (2 baje) |
|---|---|---|
| Kitabein (titles) aur copies | Sirf tabdeel shuda | Sab, aur Koha se delete shuda website se bhi hat jati hain |
| Members | Sirf tabdeel shuda | Sab |
| Jaari kitabein aur bookings | Sab | Sab |

| Website / app se Koha | Kab |
|---|---|
| Renew, reserve, booking cancel | 30 second ke andar |
| Nayi membership (admin approve kare to) | 30 second ke andar. Card number Koha deta hai, ya `MMB-000123` format mein agent bana deta hai. |
| Member ka mobile / email badalna | 30 second ke andar |

PC band ho to requests website par queue mein rehte hain, aur PC on hote hi poore ho jaate hain. Members ko ye baat website par bata di jati hai.

## Masla ho to
- **"Koha staff login failed"**: username/password, ya Koha address galat hai.
- **"Report … unexpected answer"**: report number galat hai, ya user ke paas `execute_reports` permission nahi.
- **REST API ✗**: `RESTBasicAuth` on karein aur permissions check karein.
- **Website ✗ 401**: agent key galat hai. Nayi key bana kar daalein.
- Log: `C:\ProgramData\MMBPL-Sync-Agent\agent.log`
