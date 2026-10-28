<?php
/** Starter pages the library edits in Admin → Pages. */
function install_pages(PDO $pdo): void
{
    $pages = [
        'about' => ['About the library', 'لائبریری کا تعارف',
            "Mian Muhammad Bakhsh Public Library serves the people of Mirpur and Azad Jammu & Kashmir with books, quiet reading halls and learning programs for every age.\n\nThe library is named after Mian Muhammad Bakhsh, the great Sufi poet of the region and author of Saif-ul-Malook, whose verses are still recited across Kashmir and Punjab.\n\n<h3>Our mission</h3>\nTo give everyone in Mirpur free access to knowledge, a safe place to read and study, and programs that help students, families and elders learn and grow.",
            "میاں محمد بخش پبلک لائبریری میرپور اور آزاد جموں و کشمیر کے لوگوں کو کتابیں، پُرسکون مطالعہ ہال اور ہر عمر کے لیے تعلیمی پروگرام فراہم کرتی ہے۔\n\nلائبریری کا نام خطے کے عظیم صوفی شاعر اور سیف الملوک کے خالق میاں محمد بخشؒ کے نام پر رکھا گیا ہے، جن کے اشعار آج بھی کشمیر اور پنجاب میں پڑھے جاتے ہیں۔\n\n<h3>ہمارا مقصد</h3>\nمیرپور کے ہر فرد کو علم تک آسان رسائی، مطالعہ کے لیے محفوظ جگہ اور ایسے پروگرام فراہم کرنا جو طلبہ، خاندانوں اور بزرگوں کو سیکھنے میں مدد دیں۔"],
        'rules' => ['Rules & loan policy', 'قواعد و ضوابط',
            "<h3>Borrowing</h3>\nMembers can borrow books with a valid library card. The number of books and the loan period depend on the membership type (see Membership & fees).\n\n<h3>Renewals</h3>\nBooks can be renewed online or at the desk unless another member has reserved them.\n\n<h3>Late returns</h3>\nA small fine is charged for each day a book is late. Please return books on time so others can read them.\n\n<h3>Lost or damaged books</h3>\nA lost or damaged book must be replaced or paid for.\n\n<h3>Reading halls</h3>\nKeep silence, keep phones on silent, and leave reference books on the table after reading.",
            "<h3>کتابوں کا اجرا</h3>\nاراکین کارآمد لائبریری کارڈ پر کتابیں جاری کروا سکتے ہیں۔ کتابوں کی تعداد اور مدت رکنیت کی قسم پر منحصر ہے۔\n\n<h3>تجدید</h3>\nکتاب کی تجدید آن لائن یا کاؤنٹر پر ہو سکتی ہے، بشرطیکہ کسی اور رکن نے اسے بُک نہ کیا ہو۔\n\n<h3>تاخیر سے واپسی</h3>\nتاخیر کے ہر دن پر معمولی جرمانہ ہوتا ہے۔ براہِ کرم وقت پر کتاب واپس کریں تاکہ دوسرے بھی پڑھ سکیں۔\n\n<h3>گم شدہ یا خراب کتاب</h3>\nگم شدہ یا خراب کتاب کا متبادل یا قیمت ادا کرنا ہو گی۔\n\n<h3>مطالعہ ہال</h3>\nخاموشی رکھیں، فون سائلنٹ پر رکھیں اور حوالہ جاتی کتب پڑھنے کے بعد میز پر چھوڑ دیں۔"],
        'exam-corner' => ['Exam corner', 'امتحانات کا گوشہ',
            'Guidance, job announcements and the best books in our collection for CSS, PPSC, AJKPSC, NTS and board / university exams. Study in our quiet halls every day.',
            'سی ایس ایس، پی پی ایس سی، اے جے کے پی ایس سی، این ٹی ایس اور بورڈ / یونیورسٹی امتحانات کے لیے رہنمائی، ملازمتوں کے اشتہارات اور بہترین کتب۔ روزانہ ہمارے پُرسکون ہال میں مطالعہ کریں۔'],
        'privacy' => ['Privacy policy', 'رازداری کی پالیسی',
            "We use your information only to run your library account: your loans, reservations and notifications.\n\nYour member record is kept in the library's Koha system. The website and app show you your own data only after you sign in.\n\nWe never sell or share your information. Membership application documents are seen only by library staff.\n\nTo correct your information, contact the library desk.",
            "ہم آپ کی معلومات صرف آپ کے لائبریری اکاؤنٹ (جاری شدہ کتب، بکنگ اور اطلاعات) کے لیے استعمال کرتے ہیں۔\n\nآپ کا ریکارڈ لائبریری کے کوہا سسٹم میں محفوظ ہے۔ ویب سائٹ اور ایپ آپ کو صرف سائن اِن کے بعد آپ کا اپنا ڈیٹا دکھاتی ہیں۔\n\nہم آپ کی معلومات نہ بیچتے ہیں نہ کسی کو دیتے ہیں۔ رکنیت کی دستاویزات صرف لائبریری کا عملہ دیکھتا ہے۔\n\nمعلومات درست کروانے کے لیے لائبریری کاؤنٹر سے رابطہ کریں۔"],
    ];
    $st = $pdo->prepare('INSERT IGNORE INTO pages (slug, title, title_ur, body, body_ur) VALUES (?,?,?,?,?)');
    foreach ($pages as $slug => [$t, $tu, $b, $bu]) $st->execute([$slug, $t, $tu, $b, $bu]);
}
