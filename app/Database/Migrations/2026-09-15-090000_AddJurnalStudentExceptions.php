<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Menambah catatan Jurnal dan exception siswa per Jurnal Mengajar.
 *
 * Exception siswa di sini bukan Presensi Siswa resmi. Hanya status
 * Sakit/Izin/Alpha yang dicatat untuk sesi pembelajaran pada satu Jurnal.
 */
class AddJurnalStudentExceptions extends Migration
{
    public function up()
    {
        if (! $this->db->fieldExists('catatan', 'presensi_mengajar')) {
            $this->db->query(
                'ALTER TABLE `presensi_mengajar` '
                . 'ADD COLUMN `catatan` TEXT NULL AFTER `materi`'
            );
        }

        if (! $this->db->tableExists('presensi_mengajar_siswa')) {
            $this->db->query(
                "CREATE TABLE `presensi_mengajar_siswa` (\n"
                . "  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,\n"
                . "  `id_presensi_mengajar` int(10) UNSIGNED NOT NULL,\n"
                . "  `id_siswa` int(10) UNSIGNED NOT NULL,\n"
                . "  `nama_siswa_snapshot` varchar(150) NOT NULL,\n"
                . "  `nisn_snapshot` varchar(20) DEFAULT NULL,\n"
                . "  `status` enum('Sakit','Izin','Alpha') NOT NULL,\n"
                . "  `created_at` datetime DEFAULT NULL,\n"
                . "  `updated_at` datetime DEFAULT NULL,\n"
                . "  PRIMARY KEY (`id`),\n"
                . "  UNIQUE KEY `uk_pm_siswa_parent_siswa` (`id_presensi_mengajar`,`id_siswa`),\n"
                . "  KEY `idx_pm_siswa_parent` (`id_presensi_mengajar`),\n"
                . "  KEY `idx_pm_siswa_siswa` (`id_siswa`),\n"
                . "  KEY `idx_pm_siswa_status` (`status`),\n"
                . "  CONSTRAINT `fk_pm_siswa_parent` FOREIGN KEY (`id_presensi_mengajar`) "
                . "REFERENCES `presensi_mengajar` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,\n"
                . "  CONSTRAINT `fk_pm_siswa_siswa` FOREIGN KEY (`id_siswa`) "
                . "REFERENCES `siswa` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE\n"
                . ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"
            );
        }
    }

    public function down()
    {
        if ($this->db->tableExists('presensi_mengajar_siswa')) {
            $this->db->query('DROP TABLE `presensi_mengajar_siswa`');
        }

        if ($this->db->fieldExists('catatan', 'presensi_mengajar')) {
            $this->db->query(
                'ALTER TABLE `presensi_mengajar` DROP COLUMN `catatan`'
            );
        }
    }
}
