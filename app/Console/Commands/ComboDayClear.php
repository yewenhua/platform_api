<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Http\Models\Combo;
use Illuminate\Support\Facades\Log;

class ComboDayClear extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'combo:clear';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'combo day clear';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     * 目前能处理12页乘以2500的数据
     * @return mixed
     */
    public function handle()
    {
        $hour = date('H');
        $now = date('Y-m-d H:i:s');

        //获取需要更新的用户列表及用户套餐
        DB::table('member')
            ->join('combo', 'combo.id', '=', 'member.combo_id')
            ->select('member.id', 'member.combo_id')
            ->where('member.isopen', 1)
            ->where('combo.isopen', 1)
            ->where('member.begin', '<=', $now)
            ->where('member.end', '>=', $now)
            ->where('combo.type', 'day')
            ->orderBy('member.id', 'asc')
            ->chunk(100, function($lists) {
                if($lists){
                    DB::beginTransaction();
                    try {
                        foreach ($lists as $item){
                            //获取套餐内容
                            $comboSites = DB::table('combo_site')->whereNull('deleted_at')->where('combo_id', $item->combo_id)->get();
                            if($comboSites) {
                                $combo = Combo::find($item->combo_id);
                                if($combo){
                                    DB::table('member')
                                        ->where('id', $item->id)
                                        ->update([
                                            "count" => $combo->count,
                                            "updated_at" => date('Y-m-d H:i;s')
                                        ]);
                                }

                                foreach ($comboSites as $detail) {
                                    DB::table('memcombo')
                                        ->where('member_id', $item->id)
                                        ->where('site_id', $detail->website_id)
                                        ->update([
                                            "count" => $detail->count,
                                            "updated_at" => date('Y-m-d H:i;s')
                                        ]);
                                }
                            }
                        }
                        DB::commit();
                    } catch (QueryException $ex) {
                        DB::rollback();
                    }
                }
                else{
                    return false;
                }
            });

    }
}
