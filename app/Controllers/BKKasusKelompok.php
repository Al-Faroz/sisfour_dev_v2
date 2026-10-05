<?php
namespace App\Controllers;
use App\Services\BkKasusKelompokService;

class BKKasusKelompok extends BaseController
{
    protected BkKasusKelompokService $service;
    public function __construct(){ $this->service=new BkKasusKelompokService(); }
    public function index(){ $u=$this->currentActorUserId();if($this->requestWantsJson())return $this->respond($this->service->page($u,$this->request->getGet()));return $this->response->setBody($this->renderWithLayout('bk/kasus_kelompok',['title'=>'Pelanggaran Kelompok','initial'=>$this->service->page($u,[]),'extraJs'=>['assets/js/bk/kasus-kelompok.js']]));}
    public function detail($id){return $this->respond($this->service->detail($this->currentActorUserId(),(int)$id));}
    public function create(){return $this->respond($this->service->create($this->currentActorUserId(),$this->payload()));}
    public function followUp($id){return $this->respond($this->service->createFollowUp($this->currentActorUserId(),(int)$id,$this->payload()));}
    private function payload():array{$p=$this->request->getPost();$r=$this->request->getRawInput();return array_replace(is_array($r)?$r:[],is_array($p)?$p:[]);}
    private function respond(array $x){$ok=(bool)($x['success']??false);return $this->response->setStatusCode($ok?200:match($x['code']??''){'FORBIDDEN'=>403,'NOT_FOUND'=>404,default=>422})->setJSON(['status'=>$ok?'success':'error','message'=>$x['message']??($ok?'Berhasil.':'Gagal.'),'data'=>$x]);}
}
