<?php
namespace App\Services;

use CodeIgniter\I18n\Time;
use Throwable;

class KonselingKelompokService
{
    private const TZ='Asia/Jakarta';
    private const MAX_EXPORT_ROWS=50000;
    private const ALLOWED_ROLES=['admin','operator','bk'];
    protected AuthService $authService;protected PeriodContextService $periodContext;protected BkKonselingFormSettingsService $formSettings;

    public function __construct(){ $this->authService=new AuthService();$this->periodContext=new PeriodContextService();$this->formSettings=new BkKonselingFormSettingsService(); }

    public function page(int $u,array $input):array{$a=$this->requirePermission($u,'bk_konseling.view');if(!$a['success'])return $a;$p=$this->periodContext->resolve($input);if(!$p['success'])return $p;$idT=(int)$p['selected']['id'];$limit=max(1,min(100,(int)($input['limit']??25)));$offset=max(0,(int)($input['offset']??0));$b=$this->baseBuilder($idT,$input);$c=clone $b;return ['success'=>true,'can_manage'=>$this->can($u,'bk_konseling.manage'),'can_export'=>$this->can($u,'bk_konseling.export'),'tahun_aktif'=>$p['active'],'tahun_dipilih'=>$p['selected'],'tahun_options'=>$p['options'],'options'=>$this->formSettings->options(),'rows'=>$b->orderBy('kg.tanggal','DESC')->orderBy('kg.id','DESC')->limit($limit,$offset)->get()->getResultArray(),'total'=>$c->countAllResults(),'limit'=>$limit,'offset'=>$offset];}

    public function detail(int $u,int $id):array{$a=$this->requirePermission($u,'bk_konseling.view');if(!$a['success'])return $a;$row=db_connect()->table('konseling_kelompok kg')->select('kg.*,ta.nama_tahun,ta.semester,u.username AS username_pencatat')->select('COALESCE(g.nama,p.nama,u.username) AS nama_pencatat',false)->join('tahun_ajaran ta','ta.id=kg.id_tahun')->join('users u','u.id=COALESCE(kg.updated_by,kg.created_by)','left',false)->join('guru g','g.id=u.id_guru','left')->join('pegawai p','p.id=u.id_pegawai','left')->where('kg.id',$id)->get()->getRowArray();if(!$row)return $this->fail('NOT_FOUND','Konseling Kelompok tidak ditemukan.');$members=db_connect()->table('konseling_kelompok_anggota a')->select('a.id_siswa,a.id_kelas,s.nisn,s.nama AS nama_siswa,k.nama_kelas')->join('siswa s','s.id=a.id_siswa')->join('kelas k','k.id=a.id_kelas')->where('a.id_konseling_kelompok',$id)->orderBy('k.nama_kelas','ASC')->orderBy('s.nama','ASC')->get()->getResultArray();$f=db_connect()->table('tindak_lanjut_konseling_kelompok tl')->select('tl.*,u.username AS username_pencatat')->select('COALESCE(g.nama,p.nama,u.username) AS nama_pencatat',false)->join('users u','u.id=tl.created_by','left')->join('guru g','g.id=u.id_guru','left')->join('pegawai p','p.id=u.id_pegawai','left')->where('tl.id_konseling_kelompok',$id)->orderBy('tl.tanggal','ASC')->orderBy('tl.id','ASC')->get()->getResultArray();return ['success'=>true,'can_manage'=>$this->can($u,'bk_konseling.manage'),'row'=>$row,'members'=>$members,'tindak_lanjut'=>$f,'options'=>$this->formSettings->options()];}

    public function create(int $u,array $input):array{$a=$this->requirePermission($u,'bk_konseling.manage');if(!$a['success'])return $a;$tahun=$this->periodContext->active();if($tahun===null)return $this->fail('NO_ACTIVE_YEAR','Tidak ada Tahun Ajaran aktif.');$ids=$this->studentIds($input['id_siswa']??[]);if(count($ids)<2)return $this->fail('VALIDATION','Pilih minimal 2 siswa untuk Konseling Kelompok.');$stage=$this->validateStageOne($input);if(!$stage['success'])return $stage;$members=$this->resolveMembers($ids,(int)$tahun['id']);if(count($members)!==count($ids))return $this->fail('INVALID_TARGET','Semua anggota wajib memiliki membership valid pada Tahun Ajaran aktif.');$db=db_connect();$db->transBegin();try{$now=Time::now(self::TZ)->format('Y-m-d H:i:s');$db->table('konseling_kelompok')->insert($stage['data']+['id_tahun'=>(int)$tahun['id'],'status'=>'Proses','created_by'=>$u,'created_at'=>$now,'updated_by'=>$u,'updated_at'=>$now]);$id=(int)$db->insertID();foreach($members as $m)$db->table('konseling_kelompok_anggota')->insert(['id_konseling_kelompok'=>$id,'id_siswa'=>(int)$m['id_siswa'],'id_kelas'=>(int)$m['id_kelas']]);if($db->transStatus()===false)throw new \RuntimeException('Transaksi gagal.');$db->transCommit();$this->log($u,'CREATE',"Membuat Konseling Kelompok #{$id} untuk ".count($members).' siswa');return ['success'=>true,'message'=>'Konseling Kelompok berhasil dibuat.','id'=>$id];}catch(Throwable $e){$db->transRollback();return $this->fail('SAVE_FAILED','Konseling Kelompok gagal disimpan.');}}

    public function update(int $u,int $id,array $input):array{$a=$this->requirePermission($u,'bk_konseling.manage');if(!$a['success'])return $a;$e=db_connect()->table('konseling_kelompok')->where('id',$id)->get()->getRowArray();if(!$e)return $this->fail('NOT_FOUND','Konseling Kelompok tidak ditemukan.');$v=$this->validateStageTwo($input,$e);if(!$v['success'])return $v;$latest=db_connect()->table('tindak_lanjut_konseling_kelompok')->where('id_konseling_kelompok',$id)->orderBy('tanggal','DESC')->orderBy('id','DESC')->get(1)->getRowArray();$d=$v['data'];if($latest)$d['status']=(string)$latest['status'];$d['updated_by']=$u;$d['updated_at']=Time::now(self::TZ)->format('Y-m-d H:i:s');if(!db_connect()->table('konseling_kelompok')->where('id',$id)->update($d))return $this->fail('UPDATE_FAILED','Konseling Kelompok gagal diperbarui.');$this->log($u,'UPDATE',"Memperbarui Konseling Kelompok #{$id}");return ['success'=>true,'message'=>'Konseling Kelompok berhasil diperbarui.'];}

    public function createFollowUp(int $u,int $id,array $input):array{$a=$this->requirePermission($u,'bk_konseling.manage');if(!$a['success'])return $a;$p=db_connect()->table('konseling_kelompok')->where('id',$id)->get()->getRowArray();if(!$p)return $this->fail('NOT_FOUND','Konseling Kelompok tidak ditemukan.');$v=$this->validateFollowUp($input,$p);if(!$v['success'])return $v;$db=db_connect();$db->transBegin();try{$now=Time::now(self::TZ)->format('Y-m-d H:i:s');$d=$v['data']+['id_konseling_kelompok'=>$id,'created_by'=>$u,'created_at'=>$now,'updated_by'=>$u,'updated_at'=>$now];$db->table('tindak_lanjut_konseling_kelompok')->insert($d);$db->table('konseling_kelompok')->where('id',$id)->update(['status'=>$d['status'],'updated_by'=>$u,'updated_at'=>$now]);if($db->transStatus()===false)throw new \RuntimeException('Transaksi gagal.');$db->transCommit();$this->log($u,'CREATE',"Tindak lanjut Konseling Kelompok #{$id}");return ['success'=>true,'message'=>'Tindak lanjut Konseling Kelompok berhasil disimpan.'];}catch(Throwable $e){$db->transRollback();return $this->fail('SAVE_FAILED','Tindak lanjut Konseling Kelompok gagal disimpan.');}}

    public function exportData(int $u, array $input): array
    {
        $auth = $this->requirePermission($u, 'bk_konseling.export');
        if (! $auth['success']) return $auth;

        $period = $this->periodContext->resolve($input);
        if (! $period['success']) return $period;

        $builder = $this->baseBuilder((int) $period['selected']['id'], $input);
        $total = (clone $builder)->countAllResults();
        if ($total > self::MAX_EXPORT_ROWS) {
            return $this->fail(
                'EXPORT_TOO_LARGE',
                'Konseling Kelompok melebihi 50.000 baris. Persempit filter.'
            );
        }

        $rows = $builder
            ->orderBy('kg.tanggal', 'ASC')
            ->orderBy('kg.id', 'ASC')
            ->get()
            ->getResultArray();

        $ids = array_map(
            static fn (array $row): int => (int) $row['id'],
            $rows
        );

        if ($ids === []) {
            return [
                'success' => true,
                'rows' => [],
                'members' => [],
                'follow_ups' => [],
            ];
        }

        $memberBuilder = db_connect()
            ->table('konseling_kelompok_anggota a')
            ->whereIn('a.id_konseling_kelompok', $ids);

        if ((clone $memberBuilder)->countAllResults() > self::MAX_EXPORT_ROWS) {
            return $this->fail(
                'EXPORT_TOO_LARGE',
                'Anggota Konseling Kelompok melebihi 50.000 baris. Persempit filter.'
            );
        }

        $members = $memberBuilder
            ->select(
                'a.id_konseling_kelompok, s.nisn, ' .
                's.nama AS nama_siswa, k.nama_kelas'
            )
            ->join('siswa s', 's.id = a.id_siswa')
            ->join('kelas k', 'k.id = a.id_kelas')
            ->orderBy('a.id_konseling_kelompok', 'ASC')
            ->orderBy('s.nama', 'ASC')
            ->get()
            ->getResultArray();

        $followBuilder = db_connect()
            ->table('tindak_lanjut_konseling_kelompok tl')
            ->whereIn('tl.id_konseling_kelompok', $ids);

        if ((clone $followBuilder)->countAllResults() > self::MAX_EXPORT_ROWS) {
            return $this->fail(
                'EXPORT_TOO_LARGE',
                'Tindak Lanjut Konseling Kelompok melebihi 50.000 baris. Persempit filter.'
            );
        }

        $followUps = $followBuilder
            ->select('tl.*, u.username AS username_pencatat')
            ->select(
                'COALESCE(g.nama, p.nama, u.username) AS nama_pencatat',
                false
            )
            ->join('users u', 'u.id = tl.created_by', 'left')
            ->join('guru g', 'g.id = u.id_guru', 'left')
            ->join('pegawai p', 'p.id = u.id_pegawai', 'left')
            ->orderBy('tl.id_konseling_kelompok', 'ASC')
            ->orderBy('tl.tanggal', 'ASC')
            ->get()
            ->getResultArray();

        return [
            'success' => true,
            'rows' => $rows,
            'members' => $members,
            'follow_ups' => $followUps,
        ];
    }

    private function baseBuilder(int $idT,array $input){$b=db_connect()->table('konseling_kelompok kg')->select(['kg.*','ta.nama_tahun','ta.semester','u.username AS username_pencatat'])->select('(SELECT COUNT(*) FROM konseling_kelompok_anggota a WHERE a.id_konseling_kelompok=kg.id) AS jumlah_anggota',false)->select('COALESCE(g.nama,p.nama,u.username) AS nama_pencatat',false)->join('tahun_ajaran ta','ta.id=kg.id_tahun')->join('users u','u.id=kg.created_by','left')->join('guru g','g.id=u.id_guru','left')->join('pegawai p','p.id=u.id_pegawai','left')->where('kg.id_tahun',$idT);$s=trim((string)($input['status']??''));$bi=trim((string)($input['bidang']??''));$q=trim((string)($input['search']??''));if($s!=='')$b->where('kg.status',$s);if($bi!=='')$b->where('kg.bidang',$bi);if($q!=='')$b->groupStart()->like('kg.topik',$q)->orLike('kg.uraian_masalah',$q)->groupEnd();return $b;}

    private function validateStageOne(array $i):array{$o=$this->formSettings->options();$t=trim((string)($i['tanggal']??''));$p=filter_var($i['pertemuan_ke']??null,FILTER_VALIDATE_INT);$b=trim((string)($i['bentuk_layanan']??''));$c=trim((string)($i['cara_hadir']??''));$d=trim((string)($i['bidang']??''));$top=trim((string)($i['topik']??''));if(!$this->validDate($t))return $this->fail('VALIDATION','Tanggal Konseling tidak valid.');if($p===false||$p<1||$p>99)return $this->fail('VALIDATION','Pertemuan ke- wajib 1–99.');if(!in_array($b,$o['bentuk_layanan']??[],true)||!in_array($c,$o['cara_hadir']??[],true)||!in_array($d,$o['bidang']??[],true)||!in_array($top,$o['topik'][$d]??[],true))return $this->fail('VALIDATION','Pilihan layanan/bidang/topik tidak valid.');return ['success'=>true,'data'=>['tanggal'=>$t,'pertemuan_ke'=>(int)$p,'bentuk_layanan'=>$b,'cara_hadir'=>$c,'bidang'=>$d,'topik'=>$top]];}
    private function validateStageTwo(array $i,array $e):array{$o=$this->formSettings->options();$u=trim((string)($i['uraian_masalah']??''));$h=trim((string)($i['hasil_kesepakatan']??''));$r=trim((string)($i['rencana_berikutnya']??''));$tb=trim((string)($i['tanggal_berikutnya']??''));$s=trim((string)($i['status']??'Proses'));if(!in_array($s,$o['status']??[],true))return $this->fail('VALIDATION','Status tidak valid.');if($r!==''&&!in_array($r,$o['rencana']??[],true)&&$r!==trim((string)($e['rencana_berikutnya']??'')))return $this->fail('VALIDATION','Rencana berikutnya tidak valid.');if($tb!==''&&(!$this->validDate($tb)||$tb<(string)$e['tanggal']))return $this->fail('VALIDATION','Tanggal berikutnya tidak valid.');if($s==='Selesai'&&($u===''||$h===''))return $this->fail('VALIDATION','Uraian dan hasil wajib untuk status Selesai.');return ['success'=>true,'data'=>['uraian_masalah'=>$u!==''?$u:null,'hasil_kesepakatan'=>$h!==''?$h:null,'rencana_berikutnya'=>$r!==''?$r:null,'tanggal_berikutnya'=>$tb!==''?$tb:null,'status'=>$s]];}
    private function validateFollowUp(array $i,array $p):array{$o=$this->formSettings->options();$t=trim((string)($i['tanggal']??''));$per=trim((string)($i['perkembangan']??''));$h=trim((string)($i['hasil_kesepakatan']??''));$r=trim((string)($i['rencana_berikutnya']??''));$tb=trim((string)($i['tanggal_berikutnya']??''));$s=trim((string)($i['status']??'Proses'));if(!$this->validDate($t)||$t<(string)$p['tanggal'])return $this->fail('VALIDATION','Tanggal tindak lanjut tidak valid.');if($per==='')return $this->fail('VALIDATION','Perkembangan wajib diisi.');if(!in_array($s,$o['status']??[],true))return $this->fail('VALIDATION','Status tidak valid.');if($r!==''&&!in_array($r,$o['rencana']??[],true))return $this->fail('VALIDATION','Rencana berikutnya tidak valid.');if($tb!==''&&(!$this->validDate($tb)||$tb<$t))return $this->fail('VALIDATION','Tanggal berikutnya tidak valid.');if($s==='Selesai'&&$h==='')return $this->fail('VALIDATION','Hasil/Kesepakatan wajib untuk status Selesai.');return ['success'=>true,'data'=>['tanggal'=>$t,'perkembangan'=>$per,'hasil_kesepakatan'=>$h!==''?$h:null,'rencana_berikutnya'=>$r!==''?$r:null,'tanggal_berikutnya'=>$tb!==''?$tb:null,'status'=>$s]];}

    private function resolveMembers(array $ids,int $idT):array{return $ids===[]?[]:db_connect()->table('anggota_kelas ak')->select('ak.id_siswa,ak.id_kelas,s.nisn,s.nama,k.nama_kelas')->join('siswa s','s.id=ak.id_siswa')->join('kelas k','k.id=ak.id_kelas AND k.id_tahun=ak.id_tahun')->whereIn('ak.id_siswa',$ids)->where('ak.id_tahun',$idT)->where('s.status_aktif','Aktif')->where('s.deleted_at',null)->where('k.deleted_at',null)->get()->getResultArray();}
    private function studentIds(mixed $v):array{if(!is_array($v))$v=preg_split('/\s*,\s*/',trim((string)$v))?:[];return array_values(array_unique(array_filter(array_map('intval',$v),static fn(int $id):bool=>$id>0)));}
    private function requirePermission(int $u,string $p):array{$roles=$this->authService->getUserRoles($u);return array_intersect(self::ALLOWED_ROLES,$roles)!==[]&&$this->authService->resolveScope($p,$u)==='SEMUA'?['success'=>true]:$this->fail('FORBIDDEN','Konseling Kelompok hanya untuk Admin, Operator, atau BK dengan permission terkait.');}
    private function can(int $u,string $p):bool{return array_intersect(self::ALLOWED_ROLES,$this->authService->getUserRoles($u))!==[]&&$this->authService->resolveScope($p,$u)==='SEMUA';}
    private function validDate(string $d):bool{$x=\DateTimeImmutable::createFromFormat('!Y-m-d',$d);$e=\DateTimeImmutable::getLastErrors();return $x!==false&&($e===false||($e['warning_count']===0&&$e['error_count']===0))&&$x->format('Y-m-d')===$d;}
    private function log(int $u,string $a,string $k):void{db_connect()->table('log_activity')->insert(['id_user'=>$u,'aksi'=>$a,'modul'=>'BK Konseling Kelompok','keterangan'=>$k,'waktu'=>Time::now(self::TZ)->format('Y-m-d H:i:s')]);}
    private function fail(string $c,string $m):array{return ['success'=>false,'code'=>$c,'message'=>$m];}
}
