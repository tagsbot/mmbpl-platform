<?php
/** Admin CRUD resource definitions. */
function admin_resources(): array
{
    $evCats = ['workshop' => 'Workshop', 'book_club' => 'Book club', 'seminar' => 'Seminar', 'kids' => 'Kids', 'exhibition' => 'Exhibition'];
    return [
        'events' => [
            'title' => 'Events', 'singular' => 'Event', 'table' => 'posts', 'perm' => 'events', 'scope' => ['type' => 'event'], 'icon' => 'calendar',
            'order' => 'event_start DESC', 'search' => ['title', 'title_ur', 'venue'],
            'list' => ['title' => 'Title', 'event_start' => 'Starts', 'venue' => 'Venue', 'status' => 'Status', '_regs' => 'Registrations'],
            'fields' => [
                'title' => ['Title (English)', 'text', 'required' => true], 'title_ur' => ['Title (Urdu)', 'text', 'rtl' => true],
                'category' => ['Category', 'select', 'options' => $evCats],
                'event_start' => ['Starts', 'datetime', 'required' => true], 'event_end' => ['Ends', 'datetime'],
                'venue' => ['Venue', 'text'], 'seats' => ['Seats (empty = unlimited)', 'number'],
                'registration_open' => ['Online registration open', 'checkbox', 'default' => 1],
                'excerpt' => ['Short description (English)', 'textarea'], 'excerpt_ur' => ['Short description (Urdu)', 'textarea', 'rtl' => true],
                'body' => ['Full details (English)', 'html'], 'body_ur' => ['Full details (Urdu)', 'html', 'rtl' => true],
                'image' => ['Banner image', 'image'], 'featured' => ['Featured', 'checkbox'],
                'status' => ['Status', 'select', 'options' => ['published' => 'Published', 'draft' => 'Draft']],
            ],
        ],
        'news' => [
            'title' => 'News', 'singular' => 'News article', 'table' => 'posts', 'perm' => 'news', 'scope' => ['type' => 'news'], 'icon' => 'file',
            'order' => 'created_at DESC', 'search' => ['title', 'title_ur'],
            'list' => ['title' => 'Title', 'created_at' => 'Published', 'status' => 'Status'],
            'fields' => [
                'title' => ['Title (English)', 'text', 'required' => true], 'title_ur' => ['Title (Urdu)', 'text', 'rtl' => true],
                'excerpt' => ['Summary (English)', 'textarea'], 'excerpt_ur' => ['Summary (Urdu)', 'textarea', 'rtl' => true],
                'body' => ['Article (English)', 'html'], 'body_ur' => ['Article (Urdu)', 'html', 'rtl' => true],
                'image' => ['Image', 'image'], 'featured' => ['Featured', 'checkbox'],
                'status' => ['Status', 'select', 'options' => ['published' => 'Published', 'draft' => 'Draft']],
            ],
        ],
        'applications' => [
            'title' => 'Membership applications', 'singular' => 'Application', 'table' => 'applications', 'perm' => 'applications', 'icon' => 'user-plus',
            'order' => 'status="pending" DESC, created_at DESC', 'search' => ['ref_no', 'full_name', 'phone', 'cnic'], 'statuses' => ['pending', 'approved', 'correction', 'rejected'],
            'list' => ['ref_no' => 'Ref', 'full_name' => 'Applicant', 'phone' => 'Mobile', 'membership_type' => 'Type', 'created_at' => 'Submitted', 'status' => 'Status'],
            'no_create' => true, 'custom_view' => 'admin/application',
            'fields' => [],
        ],
        'suggestions' => [
            'title' => 'Book suggestions', 'singular' => 'Suggestion', 'table' => 'suggestions', 'perm' => 'suggestions', 'icon' => 'lightbulb',
            'order' => 'created_at DESC', 'search' => ['title', 'author', 'name'], 'statuses' => ['review', 'approved', 'purchased', 'rejected'], 'no_create' => true,
            'list' => ['title' => 'Title', 'author' => 'Author', 'name' => 'Requested by', 'created_at' => 'Date', 'status' => 'Status'],
            'fields' => [
                'title' => ['Title', 'readonly'], 'author' => ['Author', 'readonly'], 'isbn' => ['ISBN', 'readonly'], 'language' => ['Language', 'readonly'],
                'reason' => ['Reason', 'readonly'], 'name' => ['Name', 'readonly'], 'phone' => ['Mobile', 'readonly'], 'email' => ['Email', 'readonly'],
                'status' => ['Status', 'select', 'options' => ['review' => 'Under review', 'approved' => 'Approved', 'purchased' => 'Purchased', 'rejected' => 'Rejected']],
            ],
        ],
        'donations' => [
            'title' => 'Book donations', 'singular' => 'Donation', 'table' => 'donations', 'perm' => 'donations', 'icon' => 'gift',
            'order' => 'created_at DESC', 'search' => ['name', 'phone', 'book_type'], 'statuses' => ['new', 'contacted', 'received', 'declined'], 'no_create' => true,
            'list' => ['name' => 'Donor', 'phone' => 'Mobile', 'book_type' => 'Books', 'quantity' => 'Qty', 'dropoff_date' => 'Drop-off', 'status' => 'Status'],
            'fields' => [
                'name' => ['Donor', 'readonly'], 'phone' => ['Mobile', 'readonly'], 'book_type' => ['Type of books', 'readonly'], 'quantity' => ['Quantity', 'readonly'],
                'dropoff_date' => ['Preferred drop-off', 'readonly'], 'note' => ['Note', 'readonly'],
                'status' => ['Status', 'select', 'options' => ['new' => 'New', 'contacted' => 'Contacted', 'received' => 'Received', 'declined' => 'Declined']],
            ],
        ],
        'feedback' => [
            'title' => 'Feedback & complaints', 'singular' => 'Message', 'table' => 'feedback', 'perm' => 'feedback', 'icon' => 'message',
            'order' => 'status="new" DESC, created_at DESC', 'search' => ['ref_no', 'name', 'message'], 'statuses' => ['new', 'in_progress', 'resolved'], 'no_create' => true,
            'list' => ['ref_no' => 'Ref', 'type' => 'Type', 'name' => 'From', 'created_at' => 'Date', 'status' => 'Status'],
            'fields' => [
                'ref_no' => ['Reference', 'readonly'], 'type' => ['Type', 'readonly'], 'name' => ['Name', 'readonly'], 'phone' => ['Mobile', 'readonly'], 'email' => ['Email', 'readonly'],
                'message' => ['Message', 'readonly'], 'attachment' => ['Attachment', 'readonly_file'],
                'reply' => ['Internal note / reply sent', 'textarea'],
                'status' => ['Status', 'select', 'options' => ['new' => 'New', 'in_progress' => 'In progress', 'resolved' => 'Resolved']],
            ],
        ],
        'archive' => [
            'title' => 'Local history archive', 'singular' => 'Archive item', 'table' => 'archive_items', 'perm' => 'archive', 'icon' => 'archive',
            'order' => 'id DESC', 'search' => ['title', 'title_ur', 'place'],
            'list' => ['title' => 'Title', 'type' => 'Type', 'year' => 'Year', 'place' => 'Place'],
            'fields' => [
                'title' => ['Title (English)', 'text', 'required' => true], 'title_ur' => ['Title (Urdu)', 'text', 'rtl' => true],
                'type' => ['Type', 'select', 'options' => ['photograph' => 'Photograph', 'newspaper' => 'Newspaper clipping', 'rare_book' => 'Rare book', 'document' => 'Document']],
                'year' => ['Year', 'text'], 'place' => ['Place', 'text'], 'source' => ['Source', 'text'], 'rights' => ['Rights', 'text', 'default' => '© Mian Muhammad Bakhsh Public Library'],
                'description' => ['Description', 'textarea'], 'image' => ['Image', 'image'], 'digitised' => ['Digitised (image online)', 'checkbox', 'default' => 1],
            ],
        ],
        'exam' => [
            'title' => 'Exam corner', 'singular' => 'Resource', 'table' => 'exam_resources', 'perm' => 'exam', 'icon' => 'graduation',
            'order' => 'id DESC', 'search' => ['title', 'body'],
            'list' => ['title' => 'Title', 'exam' => 'Exam', 'kind' => 'Kind', 'deadline' => 'Deadline'],
            'fields' => [
                'exam' => ['Exam', 'select', 'options' => ['CSS' => 'CSS', 'PPSC' => 'PPSC', 'AJKPSC' => 'AJKPSC', 'NTS' => 'NTS', 'Board' => 'Board / University']],
                'kind' => ['Kind', 'select', 'options' => ['guide' => 'Preparation guide', 'job' => 'Job announcement', 'link' => 'Useful link']],
                'title' => ['Title', 'text', 'required' => true], 'body' => ['Details', 'html'], 'url' => ['Link (optional)', 'text'], 'deadline' => ['Deadline (jobs)', 'date'],
            ],
        ],
        'gallery' => [
            'title' => 'Photo gallery', 'singular' => 'Photo', 'table' => 'gallery', 'perm' => 'gallery', 'icon' => 'image',
            'order' => 'sort, id DESC', 'search' => ['caption'],
            'list' => ['image' => 'Photo', 'caption' => 'Caption', 'sort' => 'Order'],
            'fields' => ['image' => ['Photo', 'image', 'required' => true], 'caption' => ['Caption', 'text'], 'sort' => ['Display order', 'number', 'default' => 0]],
        ],
        'users' => [
            'title' => 'Staff users & roles', 'singular' => 'User', 'table' => 'users', 'perm' => 'users', 'icon' => 'shield',
            'order' => 'id', 'search' => ['name', 'email'],
            'list' => ['name' => 'Name', 'email' => 'Email', 'role' => 'Role', 'active' => 'Active', 'last_login' => 'Last login'],
            'fields' => [
                'name' => ['Name', 'text', 'required' => true], 'email' => ['Email', 'email', 'required' => true],
                'role' => ['Role', 'select', 'options' => ['super_admin' => 'Super admin (everything)', 'librarian' => 'Librarian (members, requests, events)', 'editor' => 'Content editor (news, events, pages)']],
                'password' => ['Password (leave empty to keep)', 'password'], 'active' => ['Active', 'checkbox', 'default' => 1],
            ],
        ],
    ];
}
