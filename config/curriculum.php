<?php

/**
 * แผนการจัดการเรียนรู้ ชมรมวัคซีนจราจร (TAVIS) — รวม 14 ชั่วโมง
 * instructor_type: teacher|police|hospital|honda|transport
 */

return [
    1 => [
        'plan_no' => 1,
        'title' => 'ปัญหาอุบัติเหตุทางถนน ปัจจัยเสี่ยง และการเตรียมความพร้อมเป็นผู้ใช้รถใช้ถนนอย่างปลอดภัย',
        'hours' => 1,
        'instructor_type' => 'teacher',
        'instructor_label' => 'ครูที่ปรึกษาชมรม',
        'topic_group' => 'พื้นฐานความปลอดภัยทางถนน',
        'knowledge_sheets' => [
            'สถานการณ์อุบัติเหตุทางถนนของประเทศไทยและจังหวัด',
            'ปัจจัยเสี่ยง 4M (Man, Machine, Media, Management)',
            'การเตรียมความพร้อมก่อนออกเดินทาง',
        ],
        'worksheets' => [
            'วิเคราะห์ข่าวอุบัติเหตุและระบุปัจจัยเสี่ยง',
            'แบบสำรวจพฤติกรรมตนเองในการใช้รถใช้ถนน',
        ],
        'resources' => [
            ['type' => 'youtube', 'title' => 'สถิติอุบัติเหตุทางถนนไทย', 'url' => 'https://www.youtube.com/results?search_query=อุบัติเหตุทางถนน+ประเทศไทย'],
            ['type' => 'link', 'title' => 'ศูนย์ข้อมูลอุบัติเหตุ ThaiRSC', 'url' => 'https://www.thairsc.com/'],
        ],
    ],
    2 => [
        'plan_no' => 2,
        'title' => 'จิตสำนึกและมารยาทในการใช้รถใช้ถนน',
        'hours' => 1,
        'instructor_type' => 'teacher',
        'instructor_label' => 'ครูที่ปรึกษาชมรม',
        'topic_group' => 'พื้นฐานความปลอดภัยทางถนน',
        'knowledge_sheets' => [
            'จิตสำนึกผู้ใช้รถใช้ถนนอย่างรับผิดชอบ',
            'มารยาทบนท้องถนนและการให้เกียรติผู้อื่น',
            'วัฒนธรรมความปลอดภัย (Safety Culture)',
        ],
        'worksheets' => [
            'กรณีศึกษาพฤติกรรมไม่เหมาะสมบนท้องถนน',
            'เขียนคำมั่นสัญญาการใช้รถใช้ถนนอย่างมีมารยาท',
        ],
        'resources' => [
            ['type' => 'youtube', 'title' => 'มารยาทบนท้องถนน', 'url' => 'https://www.youtube.com/results?search_query=มารยาทการใช้รถใช้ถนน'],
        ],
    ],
    3 => [
        'plan_no' => 3,
        'title' => 'กฎหมายว่าด้วยรถยนต์ ทางหลวง จราจรทางบก และ พ.ร.บ.คุ้มครองผู้ประสบภัยจากรถ',
        'hours' => 1,
        'instructor_type' => 'police',
        'instructor_label' => 'เจ้าหน้าที่ตำรวจ / วิทยากรกฎหมาย',
        'topic_group' => 'กฎหมายจราจร',
        'knowledge_sheets' => [
            'พระราชบัญญัติรถยนต์',
            'พระราชบัญญัติทางหลวง',
            'พระราชบัญญัติจราจรทางบก',
            'พ.ร.บ.คุ้มครองผู้ประสบภัยจากรถ (พ.ร.บ.ประกันภัย)',
        ],
        'worksheets' => [
            'จับคู่ข้อกฎหมายกับสถานการณ์จริง',
            'สรุปสิทธิและหน้าที่ของผู้ใช้รถใช้ถนน',
        ],
        'resources' => [
            ['type' => 'link', 'title' => 'กฎหมายจราจรทางบก', 'url' => 'https://www.royalthaipolice.go.th/'],
            ['type' => 'pdf', 'title' => 'สรุป พ.ร.บ.คุ้มครองผู้ประสบภัยจากรถ', 'url' => ''],
        ],
    ],
    4 => [
        'plan_no' => 4,
        'title' => 'กฎหมายจราจรที่สำคัญ สัญญาณ เครื่องหมายจราจร',
        'hours' => 1,
        'instructor_type' => 'police',
        'instructor_label' => 'เจ้าหน้าที่ตำรวจจราจร',
        'topic_group' => 'กฎหมายจราจร',
        'knowledge_sheets' => [
            'สัญญาณไฟจราจรและสัญญาณมือ',
            'เครื่องหมายจราจรแนวนอนและแนวดิ่ง',
            'กฎสำคัญ: ความเร็ว แซง เลี้ยว ทางม้าลาย หมวกนิรภัย',
        ],
        'worksheets' => [
            'แบบทดสอบรู้เครื่องหมายจราจร',
            'วาดแผนที่เส้นทางบ้าน–โรงเรียนและระบุเครื่องหมาย',
        ],
        'resources' => [
            ['type' => 'youtube', 'title' => 'เครื่องหมายจราจร', 'url' => 'https://www.youtube.com/results?search_query=เครื่องหมายจราจร'],
            ['type' => 'link', 'title' => 'กรมการขนส่งทางบก', 'url' => 'https://www.dlt.go.th/'],
        ],
    ],
    5 => [
        'plan_no' => 5,
        'title' => 'การจัดการความเสี่ยงในการใช้รถใช้ถนน (TSY Module 1 ทักษะคิดเอาชีวิตรอด)',
        'hours' => 1,
        'instructor_type' => 'honda',
        'instructor_label' => 'วิทยากร Honda Safety / TSY',
        'topic_group' => 'TSY – ทักษะคิดเอาชีวิตรอด',
        'knowledge_sheets' => [
            'แนวคิด Traffic Safety Youth (TSY)',
            'ทักษะคิดเอาชีวิตรอดบนท้องถนน',
            'การระบุและประเมินความเสี่ยง',
        ],
        'worksheets' => [
            'แผนที่ความเสี่ยงเส้นทางประจำวัน',
            'กิจกรรมคิดเอาชีวิตรอดจากสถานการณ์จำลอง',
        ],
        'resources' => [
            ['type' => 'youtube', 'title' => 'Honda Safety Riding / TSY', 'url' => 'https://www.youtube.com/results?search_query=Honda+Safety+TSY'],
        ],
    ],
    6 => [
        'plan_no' => 6,
        'title' => 'สมรรถนะ ข้อจำกัด และการประมวลผลของมนุษย์ (TSY Module 2)',
        'hours' => 1,
        'instructor_type' => 'honda',
        'instructor_label' => 'วิทยากร Honda Safety / TSY',
        'topic_group' => 'TSY – ทักษะคิดเอาชีวิตรอด',
        'knowledge_sheets' => [
            'ขีดจำกัดการมองเห็น การได้ยิน ปฏิกิริยาตอบสนอง',
            'ภาระงานทางความคิดขณะขับขี่/ใช้ถนน',
            'ความเข้าใจผิดเกี่ยวกับสมรรถนะตนเอง',
        ],
        'worksheets' => [
            'ทดลองการรับรู้และเวลาปฏิกิริยา',
            'สะท้อนคิดข้อจำกัดของตนเอง',
        ],
        'resources' => [
            ['type' => 'youtube', 'title' => 'Human Limitation และการขับขี่', 'url' => 'https://www.youtube.com/results?search_query=human+factor+driving+safety'],
        ],
    ],
    7 => [
        'plan_no' => 7,
        'title' => 'การตระหนักรู้สถานการณ์และการคาดการณ์อุบัติเหตุ (TSY Module 3) — SHELL Model',
        'hours' => 1,
        'instructor_type' => 'honda',
        'instructor_label' => 'วิทยากร Honda Safety / TSY',
        'topic_group' => 'TSY – ทักษะคิดเอาชีวิตรอด',
        'knowledge_sheets' => [
            'Situation Awareness',
            'SHELL Model (Software, Hardware, Environment, Liveware)',
            'การคาดการณ์อันตรายล่วงหน้า',
        ],
        'worksheets' => [
            'วิเคราะห์คลิปสถานการณ์ด้วย SHELL',
            'แบบฝึกคาดการณ์อันตราย (Hazard Prediction)',
        ],
        'resources' => [
            ['type' => 'link', 'title' => 'SHELL Model overview', 'url' => 'https://en.wikipedia.org/wiki/SHELL_model'],
        ],
    ],
    8 => [
        'plan_no' => 8,
        'title' => 'การตัดสินใจ (TSY Module 4) — DECIDE / KYT',
        'hours' => 1,
        'instructor_type' => 'honda',
        'instructor_label' => 'วิทยากร Honda Safety / TSY',
        'topic_group' => 'TSY – ทักษะคิดเอาชีวิตรอด',
        'knowledge_sheets' => [
            'กระบวนการตัดสินใจ DECIDE',
            'KYT (Kiken Yochi Training) การชี้แนะอันตราย',
            'ทางเลือกที่ปลอดภัยเมื่อเผชิญความเสี่ยง',
        ],
        'worksheets' => [
            'ฝึก DECIDE จากสถานการณ์จำลอง',
            'กิจกรรม KYT ชี้แนะอันตราย 4 ขั้นตอน',
        ],
        'resources' => [
            ['type' => 'youtube', 'title' => 'KYT / Hazard Prediction Training', 'url' => 'https://www.youtube.com/results?search_query=KYT+kiken+yochi'],
        ],
    ],
    9 => [
        'plan_no' => 9,
        'title' => 'ทัศนคติที่เป็นอันตราย (TSY Module 5)',
        'hours' => 1,
        'instructor_type' => 'honda',
        'instructor_label' => 'วิทยากร Honda Safety / TSY',
        'topic_group' => 'TSY – ทักษะคิดเอาชีวิตรอด',
        'knowledge_sheets' => [
            'ทัศนคติเสี่ยง: แข่งรถ โชว์ฝีมือ มั่นใจเกินจริง',
            'แรงกดดันจากเพื่อนและสื่อสังคม',
            'การปรับทัศนคติสู่พฤติกรรมปลอดภัย',
        ],
        'worksheets' => [
            'วิเคราะห์ทัศนคติจากคลิป/ข่าว',
            'แผนปรับทัศนคติส่วนบุคคล',
        ],
        'resources' => [
            ['type' => 'youtube', 'title' => 'อันตรายจากทัศนคติเสี่ยงบนท้องถนน', 'url' => 'https://www.youtube.com/results?search_query=ทัศนคติเสี่ยง+ขับขี่'],
        ],
    ],
    10 => [
        'plan_no' => 10,
        'title' => 'การจัดการความเหนื่อยล้า ยา สารเสพติด โรค และปัจจัยสุขภาพ (TSY Module 6) — AMISAFE',
        'hours' => 1,
        'instructor_type' => 'hospital',
        'instructor_label' => 'บุคลากรสาธารณสุข / โรงพยาบาล',
        'topic_group' => 'TSY – สุขภาพและความปลอดภัย',
        'knowledge_sheets' => [
            'AMISAFE checklist ก่อนใช้รถใช้ถนน',
            'ผลของความเหนื่อยล้า ยา สารเสพติดต่อสมรรถนะ',
            'โรคและภาวะสุขภาพที่เพิ่มความเสี่ยง',
        ],
        'worksheets' => [
            'แบบประเมินความพร้อม AMISAFE',
            'แผนดูแลสุขภาพสำหรับผู้ใช้รถใช้ถนน',
        ],
        'resources' => [
            ['type' => 'youtube', 'title' => 'ง่วงนอนขณะขับขี่ / Fatigue', 'url' => 'https://www.youtube.com/results?search_query=fatigue+driving+safety'],
        ],
    ],
    11 => [
        'plan_no' => 11,
        'title' => 'ข้อปฏิบัติเมื่อเกิดเหตุฉุกเฉิน การให้ความช่วยเหลือและการปฐมพยาบาล',
        'hours' => 1,
        'instructor_type' => 'hospital',
        'instructor_label' => 'บุคลากรสาธารณสุข / กู้ชีพ',
        'topic_group' => 'เหตุฉุกเฉินและปฐมพยาบาล',
        'knowledge_sheets' => [
            'ลำดับการช่วยเหลือ ณ จุดเกิดเหตุ (Scene Safety)',
            'การแจ้งเหตุ 1669 / ตำรวจ / ประกันภัย',
            'ปฐมพยาบาลเบื้องต้นและห้ามเลือด',
        ],
        'worksheets' => [
            'สถานการณ์จำลองช่วยเหลือผู้ประสบภัย',
            'แบบทดสอบขั้นตอนฉุกเฉิน',
        ],
        'resources' => [
            ['type' => 'youtube', 'title' => 'ปฐมพยาบาลผู้ประสบอุบัติเหตุ', 'url' => 'https://www.youtube.com/results?search_query=ปฐมพยาบาล+อุบัติเหตุทางถนน'],
            ['type' => 'link', 'title' => 'สายด่วนฉุกเฉิน 1669', 'url' => 'https://www.niems.go.th/'],
        ],
    ],
    12 => [
        'plan_no' => 12,
        'title' => 'สาธิตการขับขี่ปลอดภัยโดยผู้เชี่ยวชาญ และการดูแลยานพาหนะ',
        'hours' => 1,
        'instructor_type' => 'honda',
        'instructor_label' => 'วิทยากร Honda Safety / ผู้เชี่ยวชาญขับขี่',
        'topic_group' => 'ทักษะปฏิบัติและยานพาหนะ',
        'knowledge_sheets' => [
            'ท่าทางการขับขี่ปลอดภัยและจุดมอง',
            'การตรวจสภาพรถก่อนใช้งาน (Pre-ride check)',
            'การดูแลบำรุงรักษารถจักรยานยนต์เบื้องต้น',
        ],
        'worksheets' => [
            'แบบตรวจสภาพรถก่อนใช้',
            'บันทึกการสังเกตจากสาธิตขับขี่ปลอดภัย',
        ],
        'resources' => [
            ['type' => 'youtube', 'title' => 'สาธิตขับขี่ปลอดภัย', 'url' => 'https://www.youtube.com/results?search_query=สาธิตการขับขี่ปลอดภัย+มอเตอร์ไซค์'],
        ],
    ],
    13 => [
        'plan_no' => 13,
        'title' => 'สรุปเนื้อหาและขั้นตอนการขอใบอนุญาตขับรถจักรยานยนต์',
        'hours' => 1,
        'instructor_type' => 'transport',
        'instructor_label' => 'เจ้าหน้าที่กรมการขนส่งทางบก',
        'topic_group' => 'ใบอนุญาตขับขี่',
        'knowledge_sheets' => [
            'คุณสมบัติผู้ขอใบอนุญาตขับรถจักรยานยนต์',
            'เอกสารและขั้นตอนที่สำนักงานขนส่ง',
            'ทบทวนเนื้อหาสำคัญทั้งหลักสูตร 13 ชั่วโมงแรก',
        ],
        'worksheets' => [
            'เช็กลิสต์เตรียมเอกสารขอใบขับขี่',
            'แผนที่ขั้นตอนขอใบอนุญาต',
        ],
        'resources' => [
            ['type' => 'link', 'title' => 'กรมการขนส่งทางบก — ใบอนุญาตขับขี่', 'url' => 'https://www.dlt.go.th/'],
            ['type' => 'youtube', 'title' => 'ขั้นตอนขอใบขับขี่รถจักรยานยนต์', 'url' => 'https://www.youtube.com/results?search_query=ขั้นตอนขอใบขับขี่+รถจักรยานยนต์'],
        ],
    ],
    14 => [
        'plan_no' => 14,
        'title' => 'ประเมินผลการเรียนรู้ (แบบทดสอบออนไลน์)',
        'hours' => 1,
        'instructor_type' => 'teacher',
        'instructor_label' => 'ครูที่ปรึกษาชมรม',
        'topic_group' => 'ประเมินผล',
        'knowledge_sheets' => [
            'สรุปจุดเรียนรู้สำคัญ 14 ชั่วโมง',
            'เกณฑ์ผ่านการประเมินของชมรม',
        ],
        'worksheets' => [
            'แบบทดสอบออนไลน์ประจำหลักสูตร',
            'แบบสะท้อนผลการเรียนรู้ส่วนบุคคล',
        ],
        'resources' => [
            ['type' => 'link', 'title' => 'แบบทดสอบออนไลน์ TAVIS (ในระบบ)', 'url' => '/curriculum/14'],
        ],
    ],
];
