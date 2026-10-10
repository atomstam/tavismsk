<?php
$org = e(setting('org_name', 'โรงเรียนเชียงยืนพิทยาคม'));
$director = e(setting('director_name', 'นายชัดสกร พิกุลทอง'));
$position = e(setting('director_position', 'ผู้อำนวยการโรงเรียนเชียงยืนพิทยาคม'));
$reporter = e(trim(($item['user_title']??'').$item['first_name'].' '.$item['last_name']));
$title = e($mod['report_title'] ?? 'รายงาน');
?>
<style>body{font-family:garuda,sans-serif;font-size:14px} table{width:100%;border-collapse:collapse} td,th{border:1px solid #333;padding:6px} h2,h4{text-align:center}</style>
<h2><?= $title ?></h2>
<p style="text-align:center"><?= $org ?></p>
<table>
<tr><th width="30%">ผู้รายงาน</th><td><?= $reporter ?></td></tr>
<tr><th>หัวข้อ/เรื่อง</th><td><?= e($item['title']) ?></td></tr>
<tr><th>หน่วยงาน</th><td><?= e($item['organization'] ?? '-') ?></td></tr>
<tr><th>วันที่</th><td><?= e(thai_date($item['start_date'] ?? null)) ?></td></tr>
</table>
<p><?= nl2br(e($item['description'] ?? '')) ?></p>
<br><br>
<table style="border:0"><tr style="border:0">
<td style="border:0;text-align:center;width:50%">ลงชื่อ................................ผู้รายงาน<br>(<?= $reporter ?>)</td>
<td style="border:0;text-align:center;width:50%">ลงชื่อ................................ผู้รับรอง<br>(<?= $director ?>)<br><?= $position ?></td>
</tr></table>
