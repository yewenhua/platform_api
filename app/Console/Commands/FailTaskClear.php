<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Models\Downlist;

class FailTaskClear extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'task:clear';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'fail task clear';

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
     *
     * @return mixed
     */
    public function handle()
    {
        //每分钟软删除
        $lists = Downlist::whereNull('deleted_at')->where('status', 'failed')->get();
        if($lists){
            foreach ($lists as $list) {
                $list->delete();
            }
        }

        //半小时硬删除
        $compare_time = date('Y-m-d H:i:s', time() - 30*60);
        $lists = Downlist::whereNotNull('deleted_at')->withTrashed()->where('created_at', '<=', $compare_time)->get();
        if($lists){
            foreach ($lists as $list) {
                $list->forceDelete();
            }
        }

        //5分钟硬删除
        $compare_time = date('Y-m-d H:i:s', time() - 5*60);
        $lists = Downlist::whereNotNull('deleted_at')->withTrashed()->where('maximum', '>=', 104857600)->where('created_at', '<=', $compare_time)->get();
        if($lists){
            foreach ($lists as $list) {
                $list->forceDelete();
            }
        }
    }
}
