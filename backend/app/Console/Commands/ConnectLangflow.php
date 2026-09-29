<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\{Artisan, Http};

class ConnectLangflow extends Command
{
    protected $signature='flowfix:connect {--check : Validate configured flow IDs without updating .env}';
    protected $description='Discover the four application flows, validate component IDs, and configure local flow IDs without printing credentials';

    public function handle(): int
    {
        $key=config('flowfix.langflow.api_key');
        if (!$key) { $this->error('Isi LANGFLOW_API_KEY di backend/.env terlebih dahulu. Ini adalah key Langflow, bukan Gemini.'); return self::FAILURE; }
        try {
            $client=Http::withHeaders(['x-api-key'=>$key])->acceptJson()->connectTimeout(5)->timeout(15);
            $base=rtrim(config('flowfix.langflow.base_url'),'/');
            $response=$client->get($base.'/api/v1/flows/');
            if (!$response->successful()) { $this->error('Langflow menolak daftar flow (HTTP '.$response->status().'). Periksa key dan kepemilikan flow.'); return self::FAILURE; }
            $data=$response->json();
            $flows=array_is_list($data)?$data:($data['items']??$data['flows']??[]);
            $variables=['submission-assistant'=>'LANGFLOW_SUBMISSION_ASSISTANT_ID','document-precheck'=>'LANGFLOW_DOCUMENT_PRECHECK_ID','reviewer-summary'=>'LANGFLOW_REVIEWER_SUMMARY_ID','revision-planner'=>'LANGFLOW_REVISION_PLANNER_ID'];
            $resolved=[];
            foreach ($variables as $name=>$variable) {
                $id=config("flowfix.langflow.flows.$name");
                $matches=collect($flows)->filter(fn($flow)=>$id?($flow['id']??null)===$id:($flow['name']??null)==='FlowFix App - '.$name)->values();
                if ($matches->count()!==1) { $this->error("$name: ditemukan {$matches->count()} kecocokan. Impor flow application/ atau isi ID secara eksplisit."); return self::FAILURE; }
                $flow=$matches->first();
                if (empty($flow['data']['nodes'])) {
                    $detail=$client->get($base.'/api/v1/flows/'.$flow['id']);
                    if (!$detail->successful()) { $this->error("$name: detail flow tidak dapat dibaca."); return self::FAILURE; }
                    $flow=$detail->json();
                }
                $nodes=collect($flow['data']['nodes']??[])->keyBy('id');
                foreach (config("flowfix.langflow.mapping.$name") as $input=>$component) {
                    if (!$nodes->has($component)) { $this->error("$name: komponen $component untuk $input tidak ditemukan. Gunakan export application/ yang sesuai."); return self::FAILURE; }
                }
                $resolved[$variable]=$flow['id'];
                $this->line($name.': '.$flow['id'].' — pemetaan komponen sesuai');
            }
            if (!$this->option('check')) {
                $path=base_path('.env');$env=file_get_contents($path);
                foreach ($resolved as $variable=>$id) {
                    $line="$variable=$id";
                    $env=preg_match('/^'.$variable.'=.*$/m',$env)?preg_replace('/^'.$variable.'=.*$/m',$line,$env):$env."\n".$line;
                }
                file_put_contents($path,$env,LOCK_EX);Artisan::call('config:clear');
                $this->info('Empat ID disimpan ke .env. Kredensial tidak ditampilkan.');
            }
            $this->info('Validasi pemetaan selesai. Jalankan aplikasi untuk menguji model; pemeriksaan ini belum menjalankan AI.');
            return self::SUCCESS;
        } catch (\Throwable) { $this->error('Tidak dapat menghubungi atau membaca kontrak Langflow. Periksa layanan lokal.');return self::FAILURE; }
    }
}
