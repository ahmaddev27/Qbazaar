<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

class PageSeeder extends Seeder
{
    public function run(): void
    {
        $pages = [
            [
                'slug' => 'about',
                'title' => ['ar' => 'عن QBazaar', 'en' => 'About QBazaar'],
                'body' => [
                    'ar' => '<p>QBazaar هو سوق قطر للإعلانات المبوبة. نربط البائعين بالمشترين في أحياءهم القريبة لبيع وشراء كل شيء — من السيارات والشقق إلى الأجهزة والأثاث.</p><p>أُسس QBazaar في الدوحة عام ٢٠٢٦ بهدف توفير منصة آمنة وودودة لكل قطري.</p>',
                    'en' => '<p>QBazaar is Qatar\'s friendly classifieds marketplace. We connect sellers with buyers in their neighbourhoods to trade everything — from cars and apartments to electronics and furniture.</p><p>Founded in Doha in 2026, QBazaar exists to make local commerce safer and friendlier for every Qatari.</p>',
                ],
                'display_order' => 1,
            ],
            [
                'slug' => 'terms',
                'title' => ['ar' => 'الشروط والأحكام', 'en' => 'Terms of Service'],
                'body' => [
                    'ar' => '<p>باستخدامك QBazaar فأنت توافق على هذه الشروط. نحتفظ بحق إزالة أي محتوى يخالف سياساتنا أو القوانين القطرية.</p>',
                    'en' => '<p>By using QBazaar you agree to these terms. We reserve the right to remove any content that violates our policies or Qatari law.</p>',
                ],
                'display_order' => 2,
            ],
            [
                'slug' => 'privacy',
                'title' => ['ar' => 'سياسة الخصوصية', 'en' => 'Privacy Policy'],
                'body' => [
                    'ar' => '<p>نحترم خصوصيتك. نجمع فقط البيانات الضرورية لتقديم الخدمة، ولا نشاركها مع أطراف ثالثة دون موافقتك.</p>',
                    'en' => '<p>We respect your privacy. We collect only the data necessary to provide the service and never share it with third parties without your consent.</p>',
                ],
                'display_order' => 3,
            ],
            [
                'slug' => 'contact',
                'title' => ['ar' => 'تواصل معنا', 'en' => 'Contact Us'],
                'body' => [
                    'ar' => '<p>هل لديك سؤال؟ راسلنا على <a href="mailto:support@qbazaar.qa">support@qbazaar.qa</a> أو افتح تذكرة دعم من حسابك.</p>',
                    'en' => '<p>Got a question? Email us at <a href="mailto:support@qbazaar.qa">support@qbazaar.qa</a> or open a support ticket from your account.</p>',
                ],
                'display_order' => 4,
            ],
        ];

        foreach ($pages as $row) {
            Page::query()->updateOrCreate(
                ['slug' => $row['slug']],
                [
                    'title' => $row['title'],
                    'body' => $row['body'],
                    'is_published' => true,
                    'published_at' => now(),
                    'display_order' => $row['display_order'],
                ],
            );
        }
    }
}
