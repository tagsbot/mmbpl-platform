<?php
/** Demo data so the platform can be tried before Koha is connected. Everything is flagged is_demo=1. */
function install_demo(PDO $pdo): void
{
    $books = [
        // title, author, lang, year, call, isbn, subjects, publisher, summary, popularity, days_ago_added, copies[[barcode, shelf, status]]
        ['آبِ حیات', 'محمد حسین آزاد', 'ur', '1880', 'U-891.4 AZA', null, 'Urdu literature; Literary history', 'Sang-e-Meel', 'A classic history of Urdu poetry and poets, written in lively prose.', 42, 20, [['30001245', 'Urdu section, Shelf U-4', 'available'], ['30001246', 'Urdu section, Shelf U-4', 'available']]],
        ['امراؤ جان ادا', 'مرزا ہادی رسوا', 'ur', '1899', 'U-891.43 RUS', null, 'Urdu literature; Novels', 'Sang-e-Meel', 'One of the earliest Urdu novels, set in nineteenth-century Lucknow.', 57, 90, [['30001872', 'Urdu section, Shelf U-6', 'checked_out']]],
        ['A Brief History of Time', 'Stephen Hawking', 'en', '1988', 'E-523 HAW', '9780553380163', 'Science; Cosmology; Physics', 'Bantam', 'An accessible introduction to the universe, black holes and time.', 38, 12, [['30002210', 'Science wing, Shelf S-2', 'checked_out'], ['30002211', 'Science wing, Shelf S-2', 'available']]],
        ['Python Crash Course', 'Eric Matthes', 'en', '2019', 'E-005.133 MAT', '9781593279288', 'Computer science; Programming', 'No Starch Press', 'A hands-on introduction to programming with Python.', 29, 5, [['30002480', 'Science wing, Shelf S-7', 'on_hold']]],
        ['سیف الملوک', 'میاں محمد بخش', 'other', '1863', 'K-891.42 BAK', null, 'Sufi poetry; Punjabi poetry; Kashmir heritage', 'Local edition', 'The celebrated Sufi epic of Mian Muhammad Bakhsh, the poet this library is named after.', 64, 40, [['30000101', 'Heritage room, Shelf H-1', 'reference'], ['30000102', 'Urdu section, Shelf U-1', 'available']]],
        ['بانگِ درا', 'علامہ محمد اقبال', 'ur', '1924', 'U-891.439 IQB', null, 'Urdu poetry; Iqbal studies', 'Iqbal Academy', 'The first Urdu poetry collection of Allama Iqbal.', 47, 120, [['30000311', 'Urdu section, Shelf U-2', 'available']]],
        ['دیوانِ غالب', 'مرزا اسد اللہ خان غالب', 'ur', '1841', 'U-891.431 GHA', null, 'Urdu poetry; Classics', 'Majlis Taraqqi-e-Adab', 'The Urdu ghazals of Mirza Ghalib.', 51, 200, [['30000420', 'Urdu section, Shelf U-2', 'available'], ['30000421', 'Urdu section, Shelf U-2', 'checked_out']]],
        ['آگ کا دریا', 'قرۃ العین حیدر', 'ur', '1959', 'U-891.437 HAI', null, 'Urdu literature; Novels', 'Sang-e-Meel', 'A sweeping novel across centuries of South Asian history.', 33, 60, [['30000530', 'Urdu section, Shelf U-6', 'available']]],
        ['راجہ گدھ', 'بانو قدسیہ', 'ur', '1981', 'U-891.437 QUD', null, 'Urdu literature; Novels', 'Sang-e-Meel', 'A widely read modern Urdu novel.', 45, 25, [['30000610', 'Urdu section, Shelf U-6', 'available'], ['30000611', 'Urdu section, Shelf U-6', 'available']]],
        ['تفہیم القرآن', 'سید ابوالاعلیٰ مودودی', 'ur', '1972', 'I-297.1227 MAU', null, 'Islamic studies; Tafseer', 'Idara Tarjuman-ul-Quran', 'Translation and commentary of the Holy Quran in Urdu.', 26, 300, [['30000701', 'Islamic studies, Shelf I-1', 'reference']]],
        ['The Sealed Nectar (Ar-Raheeq Al-Makhtum)', 'Safiur Rahman Mubarakpuri', 'en', '1979', 'I-297.63 MUB', '9789960899558', 'Islamic studies; Seerah', 'Darussalam', 'A prize-winning biography of the Prophet Muhammad (PBUH).', 31, 150, [['30000805', 'Islamic studies, Shelf I-3', 'available']]],
        ['Pakistan Affairs', 'M. Ikram Rabbani', 'en', '2019', 'E-954.91 RAB', null, 'Competitive exams; CSS; Pakistan studies', 'Caravan Book House', 'A standard text for Pakistan Affairs in CSS and other competitive exams.', 61, 8, [['30003100', 'Exam corner, Shelf C-1', 'checked_out'], ['30003101', 'Exam corner, Shelf C-1', 'available'], ['30003102', 'Exam corner, Shelf C-1', 'available']]],
        ['English Grammar in Use', 'Raymond Murphy', 'en', '2019', 'E-425 MUR', '9781108457651', 'Competitive exams; English language', 'Cambridge University Press', 'Self-study reference and practice for intermediate learners.', 55, 15, [['30003210', 'Exam corner, Shelf C-2', 'available'], ['30003211', 'Exam corner, Shelf C-2', 'available']]],
        ['Kashmir: The Unwritten History', 'Christopher Snedden', 'en', '2013', 'E-954.6 SNE', '9789350298985', 'Kashmir history; Azad Kashmir', 'HarperCollins', 'A history of the people of Jammu and Kashmir and the events of 1947.', 22, 30, [['30003330', 'History wing, Shelf HS-3', 'available']]],
        ['Sapiens: A Brief History of Humankind', 'Yuval Noah Harari', 'en', '2014', 'E-909 HAR', '9780062316097', 'History; Anthropology', 'Harper', 'How Homo sapiens came to dominate the world.', 40, 3, [['30003450', 'History wing, Shelf HS-1', 'available']]],
        ['Atomic Habits', 'James Clear', 'en', '2018', 'E-158.1 CLE', '9780735211292', 'Self-help; Psychology', 'Avery', 'Practical ways to build good habits and break bad ones.', 49, 2, [['30003560', 'General, Shelf G-4', 'checked_out'], ['30003561', 'General, Shelf G-4', 'available']]],
        ['The Little Prince', 'Antoine de Saint-Exupéry', 'en', '1943', 'C-843 SAI', '9780156012195', 'Children; Stories', 'Harcourt', 'A timeless story for children and adults.', 27, 45, [['30004001', "Children's corner, Shelf K-1", 'available']]],
        ['چاچا چھکن', 'امتیاز علی تاج', 'ur', '1940', 'C-891.43 TAJ', null, 'Children; Humour; Urdu literature', 'Ferozsons', 'Funny stories of the much-loved Chacha Chhakkan.', 24, 70, [['30004110', "Children's corner, Shelf K-2", 'available']]],
        ["Sophie's World", 'Jostein Gaarder', 'en', '1991', 'E-100 GAA', '9780374530716', 'Philosophy; Novels', 'Farrar, Straus and Giroux', 'A novel that introduces the history of philosophy.', 18, 100, [['30004220', 'General, Shelf G-2', 'available']]],
        ['Everyday Science for Competitive Exams', 'Library reference', 'en', '2022', 'E-500 EVE', null, 'Competitive exams; General science; PPSC', 'Sample publisher', 'Sample record: general science notes for PPSC and AJKPSC tests.', 36, 10, [['30003700', 'Exam corner, Shelf C-3', 'available']]],
    ];
    $bst = $pdo->prepare('INSERT INTO biblios (title, author, language, pub_year, call_number, isbn, subjects, publisher, summary, popularity, date_added, cover_url, is_demo) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,1)');
    $ist = $pdo->prepare('INSERT INTO items (biblio_id, barcode, shelf_location, call_number, status, due_date, is_demo) VALUES (?,?,?,?,?,?,1)');
    $ids = [];
    foreach ($books as $i => $b) {
        $cover = $b[5] ? 'https://covers.openlibrary.org/b/isbn/' . $b[5] . '-M.jpg?default=false' : null;
        $bst->execute([$b[0], $b[1], $b[2], $b[3], $b[4], $b[5], $b[6], $b[7], $b[8], $b[9], date('Y-m-d', strtotime("-{$b[10]} days")), $cover]);
        $bid = (int)$pdo->lastInsertId();
        $ids[$i] = $bid;
        foreach ($b[11] as $k => [$bc, $shelf, $st]) {
            $due = $st === 'checked_out' ? date('Y-m-d', strtotime('+' . (2 + $k * 5 + $i % 6) . ' days')) : null;
            $ist->execute([$bid, $bc, $shelf, $b[4], $st, $due]);
        }
    }
    $item = fn($barcode) => (int)$pdo->query('SELECT id FROM items WHERE barcode=' . $pdo->quote($barcode))->fetchColumn();

    // Test member
    $pdo->prepare('INSERT INTO members (cardnumber, firstname, surname, category, email, phone, dob, expiry_date, status, fines_due, max_loans, password_hash, activated_at, is_demo)
                   VALUES ("MMB-004521","Ayesha","Khan","Student","ayesha@example.com","0300-1234567","2000-01-15","2027-12-31","active",0,5,?,NOW(),1)')
        ->execute([password_hash('demo12345', PASSWORD_DEFAULT)]);
    $mid = (int)$pdo->lastInsertId();
    $pdo->exec('INSERT INTO members (cardnumber, firstname, surname, category, phone, dob, expiry_date, status, is_demo) VALUES
        ("MMB-004522","Bilal","Ahmed","General","0301-7654321","1995-05-20","2027-06-30","active",1),
        ("MMB-003310","Sana","Mir","Student","0302-1112223","2004-09-09","2025-06-30","expired",1)');
    $ls = $pdo->prepare('INSERT INTO loans (member_id, item_id, issued_at, due_date, renewals, renewable, not_renewable_reason) VALUES (?,?,?,?,?,?,?)');
    $ls->execute([$mid, $item('30001872'), date('Y-m-d', strtotime('-12 days')), date('Y-m-d', strtotime('+2 days')), 0, 0, 'on_reserve']);
    $ls->execute([$mid, $item('30002210'), date('Y-m-d', strtotime('-5 days')), date('Y-m-d', strtotime('+9 days')), 1, 1, null]);
    $pdo->prepare('UPDATE items SET due_date=? WHERE barcode="30001872"')->execute([date('Y-m-d', strtotime('+2 days'))]);
    $pdo->prepare('UPDATE items SET due_date=? WHERE barcode="30002210"')->execute([date('Y-m-d', strtotime('+9 days'))]);
    $pdo->prepare('INSERT INTO holds (member_id, biblio_id, status, pickup_by) VALUES (?,?,"ready",?)')->execute([$mid, $ids[3], date('Y-m-d', strtotime('+4 days'))]);
    $hs = $pdo->prepare('INSERT INTO reading_history (member_id, biblio_id, issued_at, returned_at) VALUES (?,?,?,?)');
    foreach ([[0, 40, 26], [5, 70, 55], [12, 100, 88]] as [$bi, $a, $r]) $hs->execute([$mid, $ids[$bi], date('Y-m-d', strtotime("-$a days")), date('Y-m-d', strtotime("-$r days"))]);
    $ns = $pdo->prepare('INSERT INTO notifications (member_id, type, title, body, link, created_at) VALUES (?,?,?,?,?,?)');
    $ns->execute([$mid, 'hold', 'Your reserved book is ready for pickup', '"Python Crash Course" is waiting at the circulation desk.', 'account/holds', date('Y-m-d H:i:s', strtotime('-2 hours'))]);
    $ns->execute([$mid, 'due', '"امراؤ جان ادا" is due in 2 days', 'Another member has reserved it, so it cannot be renewed.', 'account/loans', date('Y-m-d H:i:s', strtotime('-1 day'))]);

    // Events & news
    $ev = $pdo->prepare('INSERT INTO posts (type, slug, title, title_ur, excerpt, excerpt_ur, body, body_ur, category, event_start, event_end, venue, seats, featured, is_demo, created_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,1,?)');
    $d = fn($s) => date('Y-m-d H:i:s', strtotime($s));
    $ev->execute(['event', 'career-counselling-workshop', 'Career Counselling Workshop', 'کیریئر کونسلنگ ورکشاپ', 'Guidance on choosing subjects, universities and careers, with time for questions.', 'مضامین، جامعات اور کیریئر کے انتخاب پر رہنمائی، سوال و جواب کے ساتھ۔',
        "A practical session for matric, intermediate and university students.\n\n<h3>Agenda</h3><ul><li>Choosing your field after matric and FSc</li><li>Scholarships and university admissions</li><li>Preparing for CSS, PPSC and AJKPSC</li><li>Questions and answers</li></ul>", "میٹرک، انٹرمیڈیٹ اور یونیورسٹی کے طلبہ کے لیے عملی نشست۔", 'workshop', $d('next saturday 10:00'), $d('next saturday 13:00'), 'Main hall', 60, 1, $d('-3 days')]);
    $ev->execute(['event', 'book-club-urdu-novel-reading', 'Book Club: Urdu Novel Reading', 'بک کلب: اردو ناول خوانی', 'This month we read and discuss "Raja Gidh" by Bano Qudsia.', 'اس ماہ بانو قدسیہ کے ناول "راجہ گدھ" پر گفتگو۔',
        "Join fellow readers for a friendly discussion. New members are welcome; tea is served.", "قارئین کے ساتھ دوستانہ گفتگو میں شامل ہوں۔ نئے اراکین کو خوش آمدید۔", 'book_club', $d('+10 days 16:00'), $d('+10 days 18:00'), 'Reading room 2', 25, 0, $d('-5 days')]);
    $ev->execute(['event', 'childrens-story-hour', "Children's Story Hour", 'بچوں کی کہانی کی نشست', 'Stories, drawing and fun for children aged 5 to 12.', '5 سے 12 سال کے بچوں کے لیے کہانیاں اور ڈرائنگ۔',
        "Bring your children for an hour of stories in Urdu and English, followed by drawing.", "بچوں کو اردو اور انگریزی کہانیوں اور ڈرائنگ کے لیے لائیں۔", 'kids', $d('+4 days 11:00'), $d('+4 days 12:00'), "Children's corner", 30, 0, $d('-2 days')]);
    $ev->execute(['event', 'saif-ul-malook-seminar', 'Seminar: The Poetry of Saif-ul-Malook', 'سیمینار: سیف الملوک کی شاعری', 'Scholars discuss the life and Sufi poetry of Mian Muhammad Bakhsh.', 'میاں محمد بخشؒ کی زندگی اور صوفیانہ شاعری پر گفتگو۔',
        "An evening with scholars and reciters of Saif-ul-Malook.", "سیف الملوک کے محققین اور قوالوں کے ساتھ ایک شام۔", 'seminar', $d('+18 days 15:00'), $d('+18 days 18:00'), 'Main hall', 120, 0, $d('-1 days')]);
    $ev->execute(['event', 'heritage-photo-exhibition', 'Old Mirpur Photo Exhibition', 'پرانے میرپور کی تصاویر کی نمائش', 'Photographs of old Mirpur before the Mangla Dam.', 'منگلا ڈیم سے پہلے کے میرپور کی تصاویر۔',
        "A look back at the old town through photographs donated by families.", "خاندانوں کی عطیہ کردہ تصاویر کے ذریعے پرانے شہر کی جھلک۔", 'exhibition', $d('-20 days 10:00'), $d('-18 days 17:00'), 'Gallery', null, 0, $d('-30 days')]);
    $ev->execute(['news', 'new-books-arrived', 'New books for the exam corner', 'امتحانات کے گوشے کے لیے نئی کتب', 'Fresh editions for CSS, PPSC and AJKPSC preparation are now on the shelves.', 'سی ایس ایس، پی پی ایس سی اور اے جے کے پی ایس سی کی تیاری کے لیے نئی کتب دستیاب ہیں۔',
        "We have added new editions of the most requested exam preparation books. Search the catalog to check availability.", "سب سے زیادہ مانگی جانے والی کتابوں کے نئے ایڈیشن شامل کیے گئے ہیں۔", null, null, null, null, null, 0, $d('-6 days')]);
    $ev->execute(['news', 'online-services-launched', 'Library services are now online', 'لائبریری کی خدمات اب آن لائن', 'Search the catalog, renew and reserve books from home.', 'گھر بیٹھے کیٹلاگ تلاش کریں، تجدید اور بکنگ کریں۔',
        "Members can now activate an online account to see their books, renew loans and reserve titles. The mobile app is coming soon.", "اراکین اب آن لائن اکاؤنٹ فعال کر کے اپنی کتابیں دیکھ سکتے ہیں، تجدید اور بکنگ کر سکتے ہیں۔", null, null, null, null, null, 0, $d('-1 days')]);

    $ar = $pdo->prepare('INSERT INTO archive_items (title, title_ur, type, year, place, source, rights, description, digitised, is_demo) VALUES (?,?,?,?,?,?,?,?,?,1)');
    $ar->execute(['Old Mirpur bazaar (sample)', 'پرانا میرپور بازار (نمونہ)', 'photograph', '1955', 'Old Mirpur', 'Family donation', '© MMB Public Library', 'Sample record. Replace with scanned photographs.', 1]);
    $ar->execute(['Mangla Dam construction news (sample)', 'منگلا ڈیم کی تعمیر کی خبر (نمونہ)', 'newspaper', '1962', 'Mirpur', 'Newspaper archive', '© Publisher', 'Sample record of a newspaper clipping.', 0]);
    $ar->execute(['Hand-written copy of Saif-ul-Malook (sample)', 'سیف الملوک کا قلمی نسخہ (نمونہ)', 'rare_book', '1910', 'Khari Sharif', 'Library collection', '© MMB Public Library', 'Sample record of a rare manuscript held in the heritage room.', 0]);

    $ex = $pdo->prepare('INSERT INTO exam_resources (exam, kind, title, body, url, deadline, is_demo) VALUES (?,?,?,?,?,?,1)');
    $ex->execute(['CSS', 'guide', 'How to start CSS preparation', "Read the FPSC syllabus first. Make a 10-month plan, practise English essay and précis every week, and read one newspaper daily.", 'https://www.fpsc.gov.pk', null]);
    $ex->execute(['AJKPSC', 'guide', 'AJKPSC general knowledge: what to read', "Focus on Azad Kashmir geography, history, government structure and current affairs. Use the books in our exam corner.", null, null]);
    $ex->execute(['PPSC', 'link', 'PPSC official website', 'Check announcements, syllabus and past test schedules.', 'https://www.ppsc.gop.pk', null]);
    $ex->execute(['AJKPSC', 'job', 'Sample: Lecturer posts announcement', 'Sample record. Replace with real job announcements.', null, date('Y-m-d', strtotime('+20 days'))]);
    $pdo->exec("INSERT INTO settings (k, v) VALUES ('has_demo','1') ON DUPLICATE KEY UPDATE v='1'");
}
