<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $categoryId = DB::table('categories')->where('code', 'DD')->value('id');
        $uomId      = DB::table('uoms')->where('code', 'PCS')->value('id');

        $products = [
            ['code' => 'DD0073',   'name' => 'Cosse tròn trần 25, đường kính 8',                      'specification' => 'SC25-8'],
            ['code' => 'DD0096',   'name' => 'Mũ chụp cosse V14 màu đỏ',                              'specification' => 'V14DO'],
            ['code' => 'DD0097',   'name' => 'Mũ chụp cosse V14 màu vàng',                            'specification' => 'V14VA'],
            ['code' => 'DD0098',   'name' => 'Mũ chụp cosse V14 màu xanh',                            'specification' => 'V14XA'],
            ['code' => 'DD0099',   'name' => 'Mũ chụp cosse V14 màu đen',                             'specification' => 'V14DE'],
            ['code' => 'DD0100',   'name' => 'Mũ chụp cosse V8 màu đỏ',                               'specification' => 'V8DO'],
            ['code' => 'DD0101',   'name' => 'Mũ chụp cosse V8 màu vàng',                             'specification' => 'V8VA'],
            ['code' => 'DD0102',   'name' => 'Mũ chụp cosse V8 màu xanh',                             'specification' => 'V8XA'],
            ['code' => 'DD0103',   'name' => 'Mũ chụp cosse V8 màu đen',                              'specification' => 'V8DE'],
            ['code' => 'DD0104',   'name' => 'Mũ chụp cosse V5.5 màu đỏ',                             'specification' => 'V5.5DO'],
            ['code' => 'DD0105',   'name' => 'Mũ chụp cosse V5.5 màu vàng',                           'specification' => 'V5.5VA'],
            ['code' => 'DD0106',   'name' => 'Mũ chụp cosse V5.5 màu xanh',                           'specification' => 'V5.5XA'],
            ['code' => 'DD0107',   'name' => 'Mũ chụp cosse V5.5 màu đen',                            'specification' => 'V5.5DE'],
            ['code' => 'DD0108',   'name' => 'Mũ chụp cosse V3.5 màu đỏ',                             'specification' => 'V3.5DO'],
            ['code' => 'DD0109',   'name' => 'Mũ chụp cosse V3.5 màu vàng',                           'specification' => 'V3.5VA'],
            ['code' => 'DD0110',   'name' => 'Mũ chụp cosse V3.5 màu xanh',                           'specification' => 'V3.5XA'],
            ['code' => 'DD0111',   'name' => 'Mũ chụp cosse V3.5 màu đen',                            'specification' => 'V3.5DE'],
            ['code' => 'DD0120',   'name' => 'Dao cắt máy in ống lồng LM',                            'specification' => 'HC340'],
            ['code' => 'DD0121',   'name' => 'Nhãn in 5mm màu trắng LM',                              'specification' => 'LM-TP305W'],
            ['code' => 'DD0121.1', 'name' => 'Nhãn in 5mm màu trắng LM',                              'specification' => 'LM-TP505W',  'parent_code' => 'DD0121'],
            ['code' => 'DD0122',   'name' => 'Nhãn in 5mm màu vàng LM',                               'specification' => 'LM-TP305Y'],
            ['code' => 'DD0124',   'name' => 'Nhãn in 9mm màu trắng LM',                              'specification' => 'LM-TP309W'],
            ['code' => 'DD0124.1', 'name' => 'Nhãn in 9mm màu trắng LM',                              'specification' => 'LM-TP509W',  'parent_code' => 'DD0124'],
            ['code' => 'DD0125',   'name' => 'Nhãn in 9mm màu vàng LM',                               'specification' => 'LM-TP309Y'],
            ['code' => 'DD0126',   'name' => 'Nhãn in 12mm màu trắng LM',                             'specification' => 'LM-TP312W'],
            ['code' => 'DD0126.1', 'name' => 'Nhãn in 12mm màu trắng LM',                             'specification' => 'LM-TP512W',  'parent_code' => 'DD0126'],
            ['code' => 'DD0127',   'name' => 'Nhãn in 12mm màu vàng LM',                              'specification' => 'LM-TP312Y'],
            ['code' => 'DD0128',   'name' => 'Cosse pin trần 1.5, dài 8',                             'specification' => 'EN1508'],
            ['code' => 'DD0129',   'name' => 'Cosse pin có vỏ 0.5, dài 8, màu cam',                   'specification' => 'E0508'],
            ['code' => 'DD0132',   'name' => 'Cosse pin có vỏ 1.5, dài 8, đen',                       'specification' => 'E1508'],
            ['code' => 'DD0133',   'name' => 'Cosse pin có vỏ 1.5, dài 8, xanh dương',                'specification' => 'EN1508'],
            ['code' => 'DD0136',   'name' => 'Cosse pin có vỏ 2.5, dài 8, màu xanh dương',            'specification' => 'E2508'],
            ['code' => 'DD0137',   'name' => 'Cosse pin có vỏ 4.0, dài 9 màu xám',                    'specification' => 'E4009'],
            ['code' => 'DD0138',   'name' => 'Cosse pin có vỏ 6.0, dài 12 màu xanh lá',               'specification' => 'E6012'],
            ['code' => 'DD0139',   'name' => 'Cosse pin có vỏ 10, dài 12, màu trắng',                 'specification' => 'E10-12'],
            ['code' => 'DD0140',   'name' => 'Cosse pin có vỏ đuôi TE 0.5, dài 8, cam',               'specification' => 'TE0508'],
            ['code' => 'DD0143',   'name' => 'Cosse chĩa trần 1.25, dài 3',                           'specification' => 'SNB1.25-3'],
            ['code' => 'DD0144',   'name' => 'Cosse chĩa trần 1.25, dài 4',                           'specification' => 'SNB1.25-4'],
            ['code' => 'DD0145',   'name' => 'Cosse chĩa trần 2.0, dài 4',                            'specification' => 'SNB2-4'],
            ['code' => 'DD0146',   'name' => 'Cosse tròn trần 1.25, đường kính 3',                    'specification' => 'RNB1.25-3'],
            ['code' => 'DD0147',   'name' => 'Cosse tròn trần 1.25, đường kính 4',                    'specification' => 'RNB1.25-4'],
            ['code' => 'DD0148',   'name' => 'Cosse tròn trần 1.25, đường kính 5',                    'specification' => 'RNB1.25-5'],
            ['code' => 'DD0149',   'name' => 'Cosse tròn trần 2, đường kính 4',                       'specification' => 'RNB2-4'],
            ['code' => 'DD0150',   'name' => 'Cosse tròn trần 2, đường kính 5',                       'specification' => 'RNB2-5'],
            ['code' => 'DD0151',   'name' => 'Cosse tròn trần 2, đường kính 6',                       'specification' => 'RNB2-6'],
            ['code' => 'DD0152',   'name' => 'Cosse pin có vỏ 1.5, dài 8, đỏ',                        'specification' => 'Br E1508'],
            ['code' => 'DD0153',   'name' => 'Cosse tròn trần 35, đường kính 10',                     'specification' => 'SC35-10'],
            ['code' => 'DD0154',   'name' => 'Cosse tròn trần 50, đường kính 10',                     'specification' => 'SC50-10'],
            ['code' => 'DD0155',   'name' => 'Cosse tròn trần 70, đường kính 10',                     'specification' => 'SC70-10'],
            ['code' => 'DD0156',   'name' => 'Cosse tròn trần 95, đường kính 10',                     'specification' => 'SC95-10'],
            ['code' => 'DD0158',   'name' => 'Cosse tròn trần 25, đường kính 10',                     'specification' => 'SC25-10'],
            ['code' => 'DD0159',   'name' => 'Cosse tròn trần 10, đường kính 8',                      'specification' => 'SC10-8'],
            ['code' => 'DD0160',   'name' => 'Cosse tròn trần 6, đường kính 6',                       'specification' => 'SC6-6'],
            ['code' => 'DD0163',   'name' => 'Mực in Black LM',                                        'specification' => null],
            ['code' => 'DD0163.1', 'name' => 'Mực in 12mm Black LM',                                  'specification' => 'LM-IR50B',   'parent_code' => 'DD0163'],
            ['code' => 'DD0167',   'name' => 'Cosse pin trần 2.5, dài 8',                             'specification' => 'EN2508'],
            ['code' => 'DD0174',   'name' => 'Cosse nối xoắn dây điện, đường kính trong 2',           'specification' => null],
            ['code' => 'DD0175',   'name' => 'Cosse nối xoắn dây điện, đường kính trong 4',           'specification' => null],
            ['code' => 'DD0176',   'name' => 'Cosse chĩa có vỏ 1.25, dài 4, màu đỏ',                 'specification' => null],
            ['code' => 'DD0177',   'name' => 'Cosse chĩa có vỏ 1.25, dài 4, màu xanh',               'specification' => null],
            ['code' => 'DD0178',   'name' => 'Cosse chĩa có vỏ 3.5, dài 4, màu đỏ',                  'specification' => null],
            ['code' => 'DD0179',   'name' => 'Cosse chĩa có vỏ 3.5, dài 4, màu vàng',                'specification' => null],
            ['code' => 'DD0180',   'name' => 'Cosse chĩa có vỏ 3.5, dài 4, màu đen',                 'specification' => null],
            ['code' => 'DD0181',   'name' => 'Cosse chĩa có vỏ 5.5, dài 5, màu đỏ',                  'specification' => null],
            ['code' => 'DD0182',   'name' => 'Cosse chĩa có vỏ 5.5, dài 5, màu vàng',                'specification' => null],
            ['code' => 'DD0183',   'name' => 'Cosse chĩa có vỏ 5.5, dài 5, màu xanh',                'specification' => null],
            ['code' => 'DD0184',   'name' => 'Cosse chĩa có vỏ 5.5, dài 5, màu đen',                 'specification' => null],
            ['code' => 'DD0185',   'name' => 'Vòng đánh dấu dây điện số 0',                           'specification' => null],
            ['code' => 'DD0186',   'name' => 'Vòng đánh dấu dây điện số 1',                           'specification' => null],
            ['code' => 'DD0187',   'name' => 'Vòng đánh dấu dây điện số 2',                           'specification' => null],
            ['code' => 'DD0188',   'name' => 'Vòng đánh dấu dây điện số 3',                           'specification' => null],
            ['code' => 'DD0189',   'name' => 'Vòng đánh dấu dây điện số 4',                           'specification' => null],
            ['code' => 'DD0190',   'name' => 'Vòng đánh dấu dây điện số 5',                           'specification' => null],
            ['code' => 'DD0191',   'name' => 'Vòng đánh dấu dây điện số 6',                           'specification' => null],
            ['code' => 'DD0192',   'name' => 'Vòng đánh dấu dây điện số 7',                           'specification' => null],
            ['code' => 'DD0193',   'name' => 'Vòng đánh dấu dây điện số 8',                           'specification' => null],
            ['code' => 'DD0194',   'name' => 'Vòng đánh dấu dây điện số 9',                           'specification' => null],
            ['code' => 'DD0196',   'name' => 'Cosse tròn trần 35, đường kính 8',                      'specification' => 'SC35-8'],
            ['code' => 'DD0211',   'name' => 'Dây rút 4.8 x200mm Black UV',                           'specification' => null],
            ['code' => 'DD0212',   'name' => 'Dây rút 7.6 x300mm Black UV',                           'specification' => null],
            ['code' => 'DD0213',   'name' => 'Ống lồng 2.5mm LM',                                     'specification' => 'LM-TU325N'],
            ['code' => 'DD0214',   'name' => 'Ống lồng 3.2mm LM',                                     'specification' => 'LM-TU332N'],
            ['code' => 'DD0215',   'name' => 'Ống lồng 3.6mm LM',                                     'specification' => 'LM-TU336N'],
            ['code' => 'DD0216',   'name' => 'Ống lồng 4.2mm LM',                                     'specification' => 'LM-TU342N'],
            ['code' => 'DD0219',   'name' => 'Cosse tròn trần 6, đường kính 8',                       'specification' => 'SC6-8'],
            ['code' => 'DD0220',   'name' => 'Cosse tròn trần 1.25, đường kính 8',                    'specification' => 'RV1.25-8RTR'],
            ['code' => 'DD0221',   'name' => 'Dây rút 3x100 mm',                                      'specification' => null],
            ['code' => 'DD0222',   'name' => 'Dây rút 4x150 mm',                                      'specification' => null],
            ['code' => 'DD0223',   'name' => 'Dây rút 5x200 mm',                                      'specification' => null],
            ['code' => 'DD0224',   'name' => 'Dây rút 5x300 mm',                                      'specification' => null],
            ['code' => 'DD0225',   'name' => 'Dây rút 8x300 mm',                                      'specification' => null],
            ['code' => 'DD0717',   'name' => 'Ruột gà nhựa fi 20',                                    'specification' => null],
            ['code' => 'DD0718',   'name' => 'Cosse pin có vỏ 1.0, dài 8, màu vàng',                  'specification' => 'E1008'],
            ['code' => 'DD0719',   'name' => 'Cosse pin có vỏ 0.75, dài 8, màu trắng',                'specification' => 'E7508'],
            ['code' => 'DD0726',   'name' => 'Dây rút 3x150 mm màu trắng',                            'specification' => null],
            ['code' => 'DD0727',   'name' => 'Dây rút 4x200 mm màu trắng',                            'specification' => null],
            ['code' => 'DD0728',   'name' => 'Dây rút 8x300 mm màu trắng',                            'specification' => null],
        ];

        // Insert tất cả, sau đó update parent_id cho biến thể
        foreach ($products as $product) {
            DB::table('products')->insert([
                'code'                => $product['code'],
                'name'                => $product['name'],
                'category_id'         => $categoryId,
                'uom_id'              => $uomId,
                'specification'       => $product['specification'],
                'alert_before_expiry' => 0,
                'stock_rotation'      => 1,
                'image_path'          => null,
                'status'              => 1,
                'tracking_type'       => 1,
                'parent_id'           => null,
            ]);
        }

        // Update parent_id cho các biến thể
        foreach ($products as $product) {
            if (!isset($product['parent_code'])) continue;

            $parentId = DB::table('products')->where('code', $product['parent_code'])->value('id');
            if (!$parentId) continue;

            DB::table('products')
                ->where('code', $product['code'])
                ->update(['parent_id' => $parentId]);
        }
    }
}