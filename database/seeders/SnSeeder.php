<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SnSeeder extends Seeder
{
    public function run(): void
    {
        $sns = [
            ['code' => 'Kho_VT',       'name' => 'Kho Vật Tư'],
            ['code' => 'Kho_TKCK',     'name' => 'Kho Thiết Kế Cơ Khí'],
            ['code' => 'Kho_TKD',      'name' => 'Kho Thiết Kế Điện'],
            ['code' => 'Kho_IT',       'name' => 'Kho IT'],
            ['code' => 'Kho_CK',       'name' => 'Kho Cơ Khí'],
            ['code' => 'Kho_DI',       'name' => 'Kho Điện'],
            ['code' => 'Kho_KT',       'name' => 'Kho Kĩ Thuật'],
            ['code' => 'Kho_BT',       'name' => 'Kho Bảo Trì'],
            ['code' => 'Kho_KD',       'name' => 'Kho Kinh Doanh'],
            ['code' => 'Kho_LL2025',   'name' => 'Kho LL2025'],
            ['code' => 'Kho_Kim',      'name' => 'Kho Kim'],
            ['code' => 'Kho_Showroom', 'name' => 'Kho Showroom'],
            ['code' => 'TH_Kho',       'name' => 'Tiêu hao Kho'],
            ['code' => 'TH_TKCK',      'name' => 'Tiêu hao Thiết Kế Cơ Khí'],
            ['code' => 'TH_TKD',       'name' => 'Tiêu hao Thiết Kế Điện'],
            ['code' => 'TH_IT',        'name' => 'Tiêu hao IT'],
            ['code' => 'TH_CK',        'name' => 'Tiêu hao Cơ Khí'],
            ['code' => 'TH_DI',        'name' => 'Tiêu hao Điện'],
            ['code' => 'TH_KT',        'name' => 'Tiêu hao Kĩ Thuật'],
            ['code' => 'TH_BT',        'name' => 'Tiêu hao Bảo Trì'],
            ['code' => 'TH_KD',        'name' => 'Tiêu hao Kinh Doanh'],
            ['code' => 'TS_Kho',       'name' => 'Tài sản Kho'],
            ['code' => 'TS_TKCK',      'name' => 'Tài sản Thiết Kế Cơ Khí'],
            ['code' => 'TS_TKĐ',       'name' => 'Tài sản Thiết Kế Điện'],
            ['code' => 'TS_IT',        'name' => 'Tài sản IT'],
            ['code' => 'TS_CK',        'name' => 'Tài sản Cơ Khí'],
            ['code' => 'TS_DI',        'name' => 'Tài sản Điện'],
            ['code' => 'TS_KT',        'name' => 'Tài sản Kĩ Thuật'],
            ['code' => 'TS_BT',        'name' => 'Tài sản Bảo Trì'],
            ['code' => 'TS_KD',        'name' => 'Tài sản Kinh Doanh'],
            ['code' => 'VT_Moi',       'name' => 'Vật tư mới'],
            ['code' => 'VT_Loi',       'name' => 'Vật tư lỗi'],
            ['code' => 'VT_DoiMa',     'name' => 'Vật tư cần chuyển đổi mã MenT'],
            ['code' => 'Cap',          'name' => 'Đồng phục'],
            ['code' => 'Chi',          'name' => 'Vật tư xuất dùng mục đích cá nhân'],
        ];

        foreach ($sns as $sn) {
            DB::table('sns')->insert([
                'code'       => $sn['code'],
                'name'       => $sn['name'],
                'note'       => null,
                'status'     => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}