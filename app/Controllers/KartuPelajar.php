<?php

namespace App\Controllers;

use App\Services\KartuPelajarService;
use App\Services\KartuRenderService;
use CodeIgniter\HTTP\ResponseInterface;

class KartuPelajar extends BaseController
{
    protected KartuPelajarService $service;
    protected KartuRenderService $renderService;

    public function __construct()
    {
        $this->service = new KartuPelajarService();
        $this->renderService = new KartuRenderService();
    }

    public function daftar()
    {
        $userId=(int)session()->get('user_id');
        if($this->wantsJson())return$this->respond($this->service->getPage($userId,$this->request->getGet()));
        return $this->response->setBody($this->renderWithLayout('kartu/daftar',['title'=>'Kartu Pelajar','initial'=>$this->service->getPage($userId,[]),'extraJs'=>['assets/js/kartu/daftar.js']]));
    }

    public function generate(){return $this->respond($this->service->generate((int)session()->get('user_id'),$this->request->getPost()));}

    public function preview($id)
    {
        $result=$this->service->getCard((int)session()->get('user_id'),(int)$id);if(!$result['success'])return$this->respond($result);
        $data=$this->renderService->viewData($result['card']);
        if($this->wantsJson())return$this->response->setJSON(['status'=>'success','data'=>['card'=>$result['card'],'qr_payload'=>$data['qr_payload'],'verify_url'=>$data['verify_url']]]);
        return $this->response->setBody($this->renderWithLayout('kartu/preview',['title'=>'Preview Kartu Pelajar',...$data]));
    }

    public function cetak($id){return $this->download($id);}

    public function download($id)
    {
        $result=$this->service->getCard((int)session()->get('user_id'),(int)$id);if(!$result['success'])return$this->respond($result);
        $pdf=$this->renderService->pdf($this->renderService->viewData($result['card']));
        $filename='kartu_'.preg_replace('/[^A-Za-z0-9_-]+/','_',$result['card']['nomor_kartu']).'.pdf';
        return $this->response->setHeader('Content-Type','application/pdf')->setHeader('Content-Disposition','attachment; filename="'.$filename.'"')->setBody($pdf);
    }

    public function reissue($id){return$this->respond($this->service->reissue((int)session()->get('user_id'),(int)$id));}

    public function verify($code)
    {
        return view('kartu/verify',['title'=>'Verifikasi Kartu Pelajar','result'=>$this->service->verifyPublic((string)$code)]);
    }

    private function wantsJson():bool{$path=rtrim($this->request->getUri()->getPath(),'/');return$this->request->getGet('format')==='json'||$this->request->isAJAX()||str_ends_with($path,'/json');}
    private function respond(array $result){$success=(bool)($result['success']??false);return$this->response->setStatusCode($success?200:match($result['code']??''){'FORBIDDEN','NO_STUDENT_IDENTITY','NO_GURU_IDENTITY'=>ResponseInterface::HTTP_FORBIDDEN,'NOT_FOUND'=>ResponseInterface::HTTP_NOT_FOUND,default=>ResponseInterface::HTTP_UNPROCESSABLE_ENTITY,})->setJSON(['status'=>$success?'success':'error','message'=>$result['message']??($success?'Berhasil.':'Gagal.'),'data'=>$result]);}
}
