<?php
/**
 * Masa berlaku Rincian Output (ck_ro_master.berlaku_dari / berlaku_sampai).
 * Kunci periode = tahun*10 + nomor triwulan (2026 TW III → 20263); NULL = tanpa batas.
 * Pemakaian: WHERE rm.iku_kode = ? AND RO_BERLAKU  →  params [..., $k, $k] dengan $k = roKunciPeriode($db, $pid).
 */
const RO_BERLAKU = "(rm.berlaku_dari IS NULL OR rm.berlaku_dari <= ?) AND (rm.berlaku_sampai IS NULL OR rm.berlaku_sampai >= ?)";

function roKunciPeriode(PDO $db, int $periode_id): int {
    $st = $db->prepare("SELECT tahun * 10 + FIELD(triwulan, 'I', 'II', 'III', 'IV') FROM ck_periode WHERE id = ?");
    $st->execute([$periode_id]);
    return (int)$st->fetchColumn();
}
