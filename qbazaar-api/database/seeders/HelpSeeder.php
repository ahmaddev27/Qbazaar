<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\HelpArticle;
use App\Models\HelpCategory;
use Illuminate\Database\Seeder;

class HelpSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['slug' => 'getting-started', 'name' => ['ar' => 'البداية', 'en' => 'Getting Started'], 'icon' => 'Sparkles', 'order' => 1],
            ['slug' => 'buying', 'name' => ['ar' => 'الشراء', 'en' => 'Buying'], 'icon' => 'ShoppingBag', 'order' => 2],
            ['slug' => 'selling', 'name' => ['ar' => 'البيع', 'en' => 'Selling'], 'icon' => 'Tag', 'order' => 3],
            ['slug' => 'payments-safety', 'name' => ['ar' => 'الدفع والأمان', 'en' => 'Payments & Safety'], 'icon' => 'Shield', 'order' => 4],
            ['slug' => 'account-security', 'name' => ['ar' => 'الحساب والأمان', 'en' => 'Account & Security'], 'icon' => 'Lock', 'order' => 5],
        ];

        $articles = [
            'getting-started' => [
                ['slug' => 'creating-your-account', 'title' => ['ar' => 'إنشاء حسابك', 'en' => 'Creating your account'], 'body' => ['ar' => '<p>اضغط على زر «إنشاء حساب» في الزاوية وأدخل بياناتك. سنرسل لك رمز تحقق عبر الرسائل القصيرة.</p>', 'en' => '<p>Tap "Sign up" in the corner and fill in your details. We\'ll send you an SMS verification code.</p>']],
                ['slug' => 'verifying-your-phone', 'title' => ['ar' => 'تأكيد رقم الهاتف', 'en' => 'Verifying your phone'], 'body' => ['ar' => '<p>أدخل رمز التحقق المكون من 6 أرقام الذي وصلك في رسالة قصيرة. الرمز صالح لـ 5 دقائق فقط.</p>', 'en' => '<p>Enter the 6-digit code we sent via SMS. The code is valid for only 5 minutes.</p>']],
                ['slug' => 'tour-the-app', 'title' => ['ar' => 'جولة في التطبيق', 'en' => 'Tour the app'], 'body' => ['ar' => '<p>تصفح الإعلانات من الصفحة الرئيسية، استخدم البحث لإيجاد ما تريد، واحفظ المفضلة بالنقر على القلب.</p>', 'en' => '<p>Browse from Home, use search to find what you want, and save favourites by tapping the heart.</p>']],
            ],
            'buying' => [
                ['slug' => 'searching-for-items', 'title' => ['ar' => 'البحث عن المنتجات', 'en' => 'Searching for items'], 'body' => ['ar' => '<p>استخدم شريط البحث في أعلى الموقع. يمكنك التصفية بالقسم والموقع والسعر.</p>', 'en' => '<p>Use the search bar at the top. Filter by category, location and price.</p>']],
                ['slug' => 'contacting-sellers', 'title' => ['ar' => 'التواصل مع البائعين', 'en' => 'Contacting sellers'], 'body' => ['ar' => '<p>اضغط على «تواصل مع البائع» في صفحة الإعلان لبدء محادثة. تظل المحادثات داخل QBazaar للأمان.</p>', 'en' => '<p>Click "Contact seller" on the ad page to start a chat. Conversations stay inside QBazaar for safety.</p>']],
                ['slug' => 'making-an-offer', 'title' => ['ar' => 'تقديم عرض سعر', 'en' => 'Making an offer'], 'body' => ['ar' => '<p>في المحادثة، اضغط على أيقونة المصافحة لتقديم عرض سعر رسمي. ينتهي العرض بعد 7 أيام.</p>', 'en' => '<p>In the chat, tap the handshake icon to submit a formal offer. Offers expire after 7 days.</p>']],
            ],
            'selling' => [
                ['slug' => 'posting-your-first-ad', 'title' => ['ar' => 'نشر أول إعلان', 'en' => 'Posting your first ad'], 'body' => ['ar' => '<p>اضغط على «انشر إعلانك» واتبع المعالج المكون من 4 خطوات. النشر مجاني تماماً.</p>', 'en' => '<p>Click "Post ad" and follow the 4-step wizard. Posting is completely free.</p>']],
                ['slug' => 'taking-great-photos', 'title' => ['ar' => 'تصوير المنتجات', 'en' => 'Taking great photos'], 'body' => ['ar' => '<p>استخدم إضاءة طبيعية وخلفية بسيطة. ارفع 3-10 صور من زوايا مختلفة للحصول على أفضل النتائج.</p>', 'en' => '<p>Use natural light and a simple background. Upload 3-10 photos from different angles for best results.</p>']],
                ['slug' => 'managing-your-ads', 'title' => ['ar' => 'إدارة إعلاناتك', 'en' => 'Managing your ads'], 'body' => ['ar' => '<p>من «حسابي > إعلاناتي» يمكنك تعديل أو تجديد أو تمييز إعلان كمباع.</p>', 'en' => '<p>From Account > My Ads you can edit, renew, or mark an ad as sold.</p>']],
            ],
            'payments-safety' => [
                ['slug' => 'safe-meetups', 'title' => ['ar' => 'لقاءات آمنة', 'en' => 'Safe meet-ups'], 'body' => ['ar' => '<p>تقابل في أماكن عامة مزدحمة. لا تشارك بياناتك المصرفية مع البائع أو المشتري.</p>', 'en' => '<p>Meet in public busy places. Never share bank details with the seller or buyer.</p>']],
                ['slug' => 'avoiding-scams', 'title' => ['ar' => 'تجنّب الاحتيال', 'en' => 'Avoiding scams'], 'body' => ['ar' => '<p>احذر العروض التي تبدو جيدة جداً، طلبات الدفع المقدم، والروابط المشبوهة.</p>', 'en' => '<p>Beware of deals that seem too good, requests for upfront payment, and suspicious links.</p>']],
                ['slug' => 'reporting-issues', 'title' => ['ar' => 'الإبلاغ عن مشاكل', 'en' => 'Reporting issues'], 'body' => ['ar' => '<p>اضغط على «إبلاغ» في أي إعلان أو ملف شخصي مشبوه. سيراجع فريقنا البلاغ خلال 24 ساعة.</p>', 'en' => '<p>Click "Report" on any suspicious ad or profile. Our team reviews reports within 24 hours.</p>']],
            ],
            'account-security' => [
                ['slug' => 'changing-your-password', 'title' => ['ar' => 'تغيير كلمة المرور', 'en' => 'Changing your password'], 'body' => ['ar' => '<p>من حسابك > الأمان، يمكنك تغيير كلمة المرور. ستحتاج لكلمة المرور الحالية.</p>', 'en' => '<p>From Account > Security, change your password. You\'ll need your current password.</p>']],
                ['slug' => 'managing-sessions', 'title' => ['ar' => 'إدارة الجلسات', 'en' => 'Managing sessions'], 'body' => ['ar' => '<p>راجع الأجهزة المسجلة في حسابك من «الأمان > الجلسات» وأنهِ أي جلسة لا تعرفها.</p>', 'en' => '<p>Review devices signed in from Security > Sessions and terminate any you don\'t recognise.</p>']],
                ['slug' => 'deleting-your-account', 'title' => ['ar' => 'حذف حسابك', 'en' => 'Deleting your account'], 'body' => ['ar' => '<p>من «حسابي > الحساب» اطلب حذف حسابك. لديك 30 يوماً لإلغاء الطلب قبل الحذف النهائي.</p>', 'en' => '<p>From Account > Data, request deletion. You have 30 days to cancel before permanent removal.</p>']],
            ],
        ];

        foreach ($categories as $cat) {
            $category = HelpCategory::query()->updateOrCreate(
                ['slug' => $cat['slug']],
                [
                    'name' => $cat['name'],
                    'icon' => $cat['icon'],
                    'display_order' => $cat['order'],
                ],
            );

            foreach ($articles[$cat['slug']] as $idx => $a) {
                HelpArticle::query()->updateOrCreate(
                    ['slug' => $a['slug']],
                    [
                        'category_id' => $category->id,
                        'title' => $a['title'],
                        'body' => $a['body'],
                        'excerpt' => null,
                        'is_published' => true,
                        'display_order' => $idx + 1,
                    ],
                );
            }
        }
    }
}
