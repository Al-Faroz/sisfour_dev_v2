<?php
namespace App\Services;

use CodeIgniter\I18n\Time;
use Throwable;

class BkKasusKelompokService
{
    private const TZ='Asia/Jakarta';
    private const TINDAK_LANJUT=['Konseling Individu','Pembinaan','Koordinasi Wali Kelas','Pemanggilan Orang Tua','Surat Perjanjian','SP 1','SP 2','SP 3','Home Visit','Konferensi Kasus','Monitoring','Lainnya'];
    protected AuthService $authService;
    protected PeriodContextService $periodContext;
    protected BkScopeService $scopeService;

    public function __construct(){ $this->authService=new AuthService();$this->periodContext=new PeriodContextService();$this->scopeService=new BkScopeService(); }

    public function page(int $userId,array $input):array{
        $auth=$this->requireManage($userId);if(!$auth['success'])return $auth;
        $period=$this->periodContext->resolve($input);if(!$period['success'])return $period;
        $idTahun=(int)$period['selected']['id'];$limit=max(1,min(100,(int)($input['limit']??25)));$offset=max(0,(int)($input['offset']??0));$search=trim((string)($input['search']??''));
        $builder=db_connect()->table('catatan_kasus_kelompok g')
          ->select(['g.id','g.id_tahun','g.tanggal','g.id_pelanggaran','g.keterangan','g.created_by','g.created_at','rp.nama_pelanggaran','rp.kategori','ta.nama_tahun','ta.semester'])
          ->select('(SELECT COUNT(*) FROM catatan_kasus ck WHERE ck.id_kelompok = g.id) AS jumlah_anggota',false)
          ->select('COALESCE(pg.nama, gg.nama, u.username) AS nama_pencatat',false)
          ->join('ref_pelanggaran rp','rp.id=g.id_pelanggaran')->join('tahun_ajaran ta','ta.id=g.id_tahun')
          ->join('users u','u.id=g.created_by','left')->join('pegawai pg','pg.id=u.id_pegawai','left')->join('guru gg','gg.id=u.id_guru','left')
          ->where('g.id_tahun',$idTahun);
        if($search!=='')$builder->groupStart()->like('rp.nama_pelanggaran',$search)->orLike('g.keterangan',$search)->groupEnd();
        $count=clone $builder;
        return ['success'=>true,'rows'=>$builder->orderBy('g.tanggal','DESC')->orderBy('g.id','DESC')->limit($limit,$offset)->get()->getResultArray(),'total'=>$count->countAllResults(),'limit'=>$limit,'offset'=>$offset,'tahun_aktif'=>$period['active'],'tahun_dipilih'=>$period['selected'],'tahun_options'=>$period['options'],'pelanggaran'=>db_connect()->table('ref_pelanggaran')->select('id,nama_pelanggaran,kategori')->orderBy('kategori','ASC')->orderBy('nama_pelanggaran','ASC')->get()->getResultArray(),'tindak_lanjut_options'=>self::TINDAK_LANJUT];
    }

    public function detail(int $userId,int $id):array{
        $auth=$this->requireManage($userId);if(!$auth['success'])return $auth;
        $group=db_connect()->table('catatan_kasus_kelompok g')->select('g.*,rp.nama_pelanggaran,rp.kategori,ta.nama_tahun,ta.semester')->join('ref_pelanggaran rp','rp.id=g.id_pelanggaran')->join('tahun_ajaran ta','ta.id=g.id_tahun')->where('g.id',$id)->get()->getRowArray();
        if(!$group)return $this->fail('NOT_FOUND','Catatan Pelanggaran Kelompok tidak ditemukan.');
        $members=db_connect()->table('catatan_kasus ck')->select('ck.id AS id_kasus,ck.id_siswa,s.nisn,s.nama AS nama_siswa,k.nama_kelas')->join('siswa s','s.id=ck.id_siswa')->join('anggota_kelas ak','ak.id_siswa=ck.id_siswa AND ak.id_tahun=ck.id_tahun','left',false)->join('kelas k','k.id=ak.id_kelas','left')->where('ck.id_kelompok',$id)->orderBy('s.nama','ASC')->get()->getResultArray();
        $follow=db_connect()->table('tindak_lanjut_kasus tl')->select('tl.*,ck.id_siswa,s.nisn,s.nama AS nama_siswa')->join('catatan_kasus ck','ck.id=tl.id_kasus')->join('siswa s','s.id=ck.id_siswa')->where('ck.id_kelompok',$id)->orderBy('tl.tanggal','ASC')->orderBy('tl.id','ASC')->get()->getResultArray();
        return ['success'=>true,'group'=>$group,'members'=>$members,'tindak_lanjut'=>$follow,'tindak_lanjut_options'=>self::TINDAK_LANJUT];
    }

    public function create(int $userId,array $input):array{
        $auth=$this->requireManage($userId);if(!$auth['success'])return $auth;
        $tahun=$this->periodContext->active();if($tahun===null)return $this->fail('NO_ACTIVE_YEAR','Tidak ada Tahun Ajaran aktif.');
        $idTahun=(int)$tahun['id'];$ids=$this->studentIds($input['id_siswa']??[]);$idPelanggaran=(int)($input['id_pelanggaran']??0);$tanggal=trim((string)($input['tanggal']??''));$keterangan=trim((string)($input['keterangan']??''));
        if(count($ids)<2)return $this->fail('VALIDATION','Pilih minimal 2 siswa untuk pencatatan kelompok.');
        if($idPelanggaran<=0||!$this->validDate($tanggal))return $this->fail('VALIDATION','Pelanggaran dan tanggal wajib valid.');
        if(db_connect()->table('ref_pelanggaran')->where('id',$idPelanggaran)->countAllResults()<1)return $this->fail('INVALID_TARGET','Pelanggaran tidak ditemukan.');
        $students=$this->resolveStudents($ids,$idTahun);if(count($students)!==count($ids))return $this->fail('INVALID_TARGET','Semua anggota wajib siswa aktif dan mempunyai kelas pada Tahun Ajaran aktif.');
        $db=db_connect();$db->transBegin();
        try{$now=Time::now(self::TZ)->format('Y-m-d H:i:s');$db->table('catatan_kasus_kelompok')->insert(['id_tahun'=>$idTahun,'tanggal'=>$tanggal,'id_pelanggaran'=>$idPelanggaran,'keterangan'=>$keterangan!==''?$keterangan:null,'created_by'=>$userId,'created_at'=>$now,'updated_by'=>$userId,'updated_at'=>$now]);$idGroup=(int)$db->insertID();$idGuru=$this->scopeService->userGuruId($userId);
            foreach($students as $s){$db->table('catatan_kasus')->insert(['id_tahun'=>$idTahun,'id_kelompok'=>$idGroup,'id_siswa'=>(int)$s['id'],'id_pelanggaran'=>$idPelanggaran,'tanggal'=>$tanggal,'keterangan'=>$keterangan!==''?$keterangan:null,'id_guru_input'=>$idGuru,'created_at'=>$now,'updated_at'=>$now,'updated_by'=>$userId]);}
            if($db->transStatus()===false)throw new \RuntimeException('Transaksi gagal.');$db->transCommit();$this->log($userId,'CREATE',"Membuat Pelanggaran Kelompok #{$idGroup} untuk ".count($students).' siswa');
            return ['success'=>true,'message'=>count($students).' Catatan Pelanggaran siswa berhasil dibuat dalam satu kejadian kelompok.','id'=>$idGroup,'total_anggota'=>count($students)];
        }catch(Throwable $e){$db->transRollback();return $this->fail('SAVE_FAILED','Catatan Pelanggaran Kelompok gagal disimpan.');}
    }

    public function createFollowUp(int $userId,int $idGroup,array $input):array{
        $auth=$this->requireManage($userId);if(!$auth['success'])return $auth;$tanggal=trim((string)($input['tanggal']??''));$jenis=trim((string)($input['tindak_lanjut']??''));$keterangan=trim((string)($input['keterangan']??''));
        if(!$this->validDate($tanggal)||!in_array($jenis,self::TINDAK_LANJUT,true))return $this->fail('VALIDATION','Tanggal dan jenis tindak lanjut wajib valid.');
        $children=db_connect()->table('catatan_kasus')->select('id')->where('id_kelompok',$idGroup)->get()->getResultArray();if($children===[])return $this->fail('NOT_FOUND','Anggota Pelanggaran Kelompok tidak ditemukan.');
        $db=db_connect();$db->transBegin();try{$now=Time::now(self::TZ)->format('Y-m-d H:i:s');foreach($children as $child){$db->table('tindak_lanjut_kasus')->insert(['id_kasus'=>(int)$child['id'],'tanggal'=>$tanggal,'tindak_lanjut'=>$jenis,'keterangan'=>$keterangan!==''?$keterangan:null,'id_user_input'=>$userId,'created_at'=>$now,'updated_at'=>$now]);}if($db->transStatus()===false)throw new \RuntimeException('Transaksi gagal.');$db->transCommit();$this->log($userId,'CREATE',"Tindak lanjut seluruh Pelanggaran Kelompok #{$idGroup}");return ['success'=>true,'message'=>'Tindak lanjut berhasil diterapkan ke seluruh anggota kelompok.'];}catch(Throwable $e){$db->transRollback();return $this->fail('SAVE_FAILED','Tindak lanjut kelompok gagal disimpan.');}
    }

    private function resolveStudents(array $ids,int $idTahun):array{return $ids===[]?[]:db_connect()->table('siswa s')->select('s.id,s.nisn,s.nama,ak.id_kelas,k.nama_kelas')->join('anggota_kelas ak','ak.id_siswa=s.id')->join('kelas k','k.id=ak.id_kelas AND k.id_tahun=ak.id_tahun')->whereIn('s.id',$ids)->where('ak.id_tahun',$idTahun)->where('s.status_aktif','Aktif')->where('s.deleted_at',null)->where('k.deleted_at',null)->get()->getResultArray();}
    private function studentIds(mixed $v):array{if(!is_array($v))$v=preg_split('/\s*,\s*/',trim((string)$v))?:[];return array_values(array_unique(array_filter(array_map('intval',$v),static fn(int $id):bool=>$id>0)));}
    private function requireManage(int $u):array{return $this->authService->resolveScope('bk_kasus.manage',$u)==='SEMUA'?['success'=>true]:$this->fail('FORBIDDEN','Pelanggaran Kelompok hanya dapat dikelola oleh actor dengan scope SEMUA.');}
    private function validDate(string $d):bool{$x=\DateTimeImmutable::createFromFormat('!Y-m-d',$d);$e=\DateTimeImmutable::getLastErrors();return $x!==false&&($e===false||($e['warning_count']===0&&$e['error_count']===0))&&$x->format('Y-m-d')===$d;}
    private function log(int $u,string $a,string $k):void{db_connect()->table('log_activity')->insert(['id_user'=>$u,'aksi'=>$a,'modul'=>'BK Pelanggaran Kelompok','keterangan'=>$k,'waktu'=>Time::now(self::TZ)->format('Y-m-d H:i:s')]);}
    private function fail(string $c,string $m):array{return ['success'=>false,'code'=>$c,'message'=>$m];}
}
