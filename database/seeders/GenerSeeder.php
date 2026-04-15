<?php

namespace Database\Seeders;

use App\Models\Gener;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class GenerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
      $geners=['أكشن','مغامرة','رعب','خيال','خيال علمي',
      'دراما','رومانسي','غموض','كوميديا','تاريخي','جريمة',
      'سياسي','فلسفة','نفسي','فرق زمني','تعليمي','اقتصاد',
      'علوم','الطب','ديني','الحرب','كارثة','أساطير','اجتماعي',
      'الفضاء','تراجيدي','فانتازيا','سريالي','بوليسي','علم النفس',
      'جغرافيا','فيزياء وكيمياء','رياضيات','تمريض','هندسة','بحار ومحيطات',
      'إدارة أعمال','تسويق','الأدب','اللغات والترجمة','سيرة ذاتية','تشويق'];
      
   foreach($geners as $gener){
    Gener::create(['name'=> $gener]);

   }
      }
}