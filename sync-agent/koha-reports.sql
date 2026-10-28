-- =====================================================================
-- MMBPL Sync Agent: 5 saved SQL reports to create in Koha
-- Koha staff → Reports → Create from SQL. Create each report below,
-- give it the name shown, and write down the report number (id).
-- Then type the 5 numbers into the Sync Agent settings.
-- Do NOT mark these reports as "public".
-- Works with Koha 17.05 and newer.
-- =====================================================================

-- REPORT 1 — name: "MMBPL sync 1: biblios"
SELECT b.biblionumber,
       b.title,
       ExtractValue(m.metadata, '//datafield[@tag="245"]/subfield[@code="b"]') AS subtitle,
       b.author,
       bi.isbn,
       bi.publishercode AS publisher,
       COALESCE(NULLIF(bi.publicationyear, ''), b.copyrightdate) AS pub_year,
       bi.editionstatement AS edition,
       bi.pages,
       ExtractValue(m.metadata, '//datafield[@tag="041"]/subfield[@code="a"]') AS lang041,
       SUBSTRING(ExtractValue(m.metadata, '//controlfield[@tag="008"]'), 36, 3) AS lang008,
       bi.itemtype,
       ExtractValue(m.metadata, '//datafield[@tag="650"]/subfield[@code="a"]') AS subjects,
       ExtractValue(m.metadata, '//datafield[@tag="520"]/subfield[@code="a"]') AS summary,
       bi.cn_class AS call_number,
       b.datecreated AS date_added
FROM biblio b
JOIN biblioitems bi ON bi.biblionumber = b.biblionumber
JOIN biblio_metadata m ON m.biblionumber = b.biblionumber
WHERE (b.timestamp >= <<Changed since>> OR bi.timestamp >= <<Changed since again>>)
  AND b.biblionumber > <<After biblionumber>>
ORDER BY b.biblionumber
LIMIT 2000

-- REPORT 2 — name: "MMBPL sync 2: items"
SELECT i.itemnumber, i.biblionumber, i.barcode, i.itemcallnumber,
       COALESCE(loc.lib, i.location) AS location,
       i.onloan, i.itemlost, i.damaged, i.withdrawn, i.notforloan, i.issues,
       (SELECT r.found FROM reserves r WHERE r.itemnumber = i.itemnumber AND r.found IN ('W','T') LIMIT 1) AS hold_found
FROM items i
LEFT JOIN authorised_values loc ON loc.category = 'LOC' AND loc.authorised_value = i.location
WHERE i.timestamp >= <<Changed since>>
  AND i.itemnumber > <<After itemnumber>>
ORDER BY i.itemnumber
LIMIT 5000

-- REPORT 3 — name: "MMBPL sync 3: patrons"
SELECT p.borrowernumber, p.cardnumber, p.firstname, p.surname, p.categorycode,
       p.email,
       COALESCE(NULLIF(p.mobile, ''), NULLIF(p.phone, ''), p.phonepro) AS phone,
       p.dateofbirth, p.dateexpiry,
       (p.debarred IS NOT NULL AND p.debarred >= CURDATE()) AS blocked,
       p.lost, p.gonenoaddress,
       (SELECT COALESCE(SUM(a.amountoutstanding), 0) FROM accountlines a WHERE a.borrowernumber = p.borrowernumber) AS fines
FROM borrowers p
WHERE p.updated_on >= <<Changed since>>
  AND p.borrowernumber > <<After borrowernumber>>
ORDER BY p.borrowernumber
LIMIT 5000

-- REPORT 4 — name: "MMBPL sync 4: checkouts"
SELECT iss.*,
       i.biblionumber,
       EXISTS (SELECT 1 FROM reserves r WHERE r.biblionumber = i.biblionumber AND r.found IS NULL) AS on_reserve
FROM issues iss
JOIN items i ON i.itemnumber = iss.itemnumber

-- REPORT 5 — name: "MMBPL sync 5: holds"
SELECT r.reserve_id, r.borrowernumber, r.biblionumber, r.itemnumber, r.found,
       r.priority, r.waitingdate, r.expirationdate
FROM reserves r
