<?php
/**
 * Yi Mai Yoga site data (converted from yimaiyoga.com data/site-config.json)
 * Theme: yimaiyoga
 */

function yimai_site_data(): array
{
    $default = [
        'site' => [
            'name' => '一麦瑜伽·普拉提',
            'brand' => 'YI MAI',
                'logo' => '',
                'logoHeight' => 13,
            'url' => 'https://www.yimaiyoga.com',
            'title' => '一麦瑜伽·普拉提 | 宁波高端瑜伽普拉提空间',
            'description' => '一麦瑜伽·普拉提是宁波高端瑜伽普拉提运动连锁品牌，提供精品团课、私教小班、定制私教、身心疗愈与 RYT200 瑜伽教培。',
            'keywords' => [
                '宁波瑜伽',
                '宁波普拉提',
                '一麦瑜伽',
                '瑜伽私教',
                '普拉提私教',
                'RYT200教培',
            ],
            'favicon' => '/favicon.png',
            'icpNumber' => '',
            'wechatQr' => '/images/wechat-official-account.jpg',
            // 企业微信 Webhook 不入库不入 git：运行时优先读后台保存的 site.wecomWebhook，
            // 其次读 inc/local-secrets.php 的 wecom_webhook（见 functions.php yimai_wecom_webhook()）
            'wecomWebhook' => '',
            // 图床（后台「基础与SEO」可配置；留空时回退 local-secrets.php，都没有则仅存本地）
            'imgbedDomain' => '',
            'imgbedAuthCode' => '',
            // 新上传图片默认写入哪种地址：imgbed=图床链接（需已配置图床），local=仅本地
            'uploadTarget' => 'imgbed',
            'theme' => 'ebony-ivory',
            'customTheme' => [
                'linen' => '#ffffff',
                'oat' => '#d8d0c2',
                'clay' => '#a9795f',
                'moss' => '#6c725c',
                'forest' => '#1d241f',
                'sage' => '#a8aa9b',
                'rose' => '#c9a59b',
                'ink' => '#1b1c18',
                'pearl' => '#fbfaf6',
                'stone' => '#c9c3b8',
                'smoke' => '#8a897f',
            ],
        ],
        'images' => [
            'homeHero' => '/uploads/1778003835643-e3b7a4a63cad.jpg',
            'homeStudio' => '/uploads/1778002384487-e9a5b07e47f328.jpg',
            // 以下均改为门店实拍图（不再依赖 Unsplash 外链，国内访问不可靠）：
            // homeStory 用课程图 #12（首页课程卡只展示前 6 张，避免同页重复）
            'homeStory' => '/uploads/1778002256846-dbaf851d6a0fa8.jpg',
            // 预约页不展示课程卡片，与首页课程图 #6 不冲突
            'bookingHero' => '/uploads/1778002203377-a80bf577ed8a58.jpg',
            // 空间页主图用带品牌墙的前台渲染图
            'studioHero' => '/uploads/1778004009405-756a7e56227d18.jpg',
            'studioImages' => [
                '/uploads/1778004033976-279450b41c2ec.jpg',
                '/uploads/1778002210428-befbc7de39c5f.jpg',
                '/uploads/1778002237590-f3715ff8e98d88.jpg',
            ],
        ],
        'copy' => [
            'home' => [
                'heroEyebrow' => 'Yi Mai Yoga & Pilates · Ningbo',
                'heroTitle' => '在身体里，
找回松弛的力量。',
                'heroDescription' => '宁波高端瑜伽普拉提空间。为女性身体建立力量、秩序与松弛感，让练习成为一种更精致的生活方式。',
                'heroNote' => '看见身体，包容当下，在一次次稳定练习里超越自己。',
                'philosophyEyebrow' => 'Philosophy · 品牌理念',
                'philosophyTitle' => '练习，是一种更温柔的自我尊重。',
                'philosophyDescription' => '一麦不把练习推向更快、更满、更热闹。我们相信高级的身体体验，来自精准、节制、被照顾的细节。',
                'themesEyebrow' => 'Practice Themes · 课程主题',
                'themesTitle' => '丰富主题，回应不同身体需求。',
                'themesDescription' => '从肩颈理疗、灵活脊柱到普拉提核心床、空中瑜伽与颂钵疗愈，一麦用丰富主题和多时段排课，承接不同阶段会员的练习需求。',
                'studioEyebrow' => 'The Studio · 空间',
                'studioTitle' => '让城市慢下来的一处身体空间。',
                'studioDescription' => '宁波两家直营旗舰店，单店 500㎡+。休息区、私教室、普拉提器械、墙绳与疗愈空间共同构成一次更完整的到店体验。',
                'instructorsEyebrow' => 'Instructors · 师资',
                'instructorsTitle' => '好的老师，懂得克制地托住你。',
                'instructorsDescription' => '高端感不来自堆砌认证，而来自老师对边界、节奏、辅助与个体差异的判断。一麦主理人与多年全职师资共同承接练习结果。',
                'membershipEyebrow' => 'Membership · 会员',
                'membershipTitle' => '丰富课程与多时段排课，回应不同阶段的身体需求。',
                'storyEyebrow' => 'Begin Again · 空间故事',
                'storyTitle' => '身体会记得那些让人安心的空间。',
                'bookingEyebrow' => 'Booking · 预约',
                'bookingTitle' => '第一次到店，也应该被认真安排。',
                'bookingDescription' => '提交意向后，我们会基于练习目标、身体状态和到店时间，推荐适合的课程与老师。建议提前一天预约。',
            ],
            'classes' => [
                'eyebrow' => '课程主题',
                'title' => '不是只有一种瑜伽，而是为不同身体状态准备不同主题。',
                'sideEyebrow' => 'Theme / Effect / Fit',
                'sideDescription' => '用户真正关心的不是课程分类，而是“我适合什么、能改善什么、什么时间可以练”。一麦用丰富主题和多时段排课，承接不同阶段的身体需求。',
                'chooseEyebrow' => 'How to choose',
                'chooseTitle' => '私教、小班、团课，承接不同练习目标。',
            ],
            'instructors' => [
                'eyebrow' => '师资团队',
                'title' => '好的辅助，克制而准确。',
                'sideEyebrow' => 'Teacher Notes',
                'sideDescription' => '本页为部分师资展示。一麦品牌旗下 30+ 位老师，覆盖瑜伽、普拉提、理疗、孕产与疗愈方向，更多老师可到店探索。',
            ],
            'membership' => [
                'eyebrow' => '会员与练习方案',
                'title' => '先选择节奏，再选择方案。',
                'sideEyebrow' => 'Practice Note',
                'sideDescription' => '不同阶段的会员，需要不同的练习密度与课程主题。我们会结合时间安排、身体状态和阶段目标，推荐更适合你的练习方案。',
                'sideTitle' => '先评估，再安排。',
                'ctaEyebrow' => 'Private Consultation',
                'ctaTitle' => '不确定适合哪一种？先从一次体验开始。',
            ],
            'booking' => [
                'eyebrow' => '预约体验',
                'title' => '第一次到店，也应该被认真安排。',
                'description' => '留下你的基础信息，我们会根据练习目标、身体状态和方便到店时间，推荐适合的课程与老师。',
            ],
            'studio' => [
                'eyebrow' => '空间与品牌故事',
                'title' => '空间先安静，身体才会打开。',
                'sideEyebrow' => 'Studio Notes',
                'sideDescription' => '一麦的空间不追求过度装饰，而是让光线、尺度、气味和动线共同服务一次舒展的练习体验。',
                'heroEyebrow' => 'Light / Scale / Quietness',
                'heroTitle' => '一处真正适合停下来呼吸的地方。',
            ],
            'contact' => [
                'eyebrow' => '联系与常见问题',
                'title' => '有些问题，适合先聊一聊。',
                'faqEyebrow' => 'FAQ · 到店前常见问题',
                'faqTitle' => '把不确定先放下，我们会帮你确认适合的开始方式。',
            ],
            'training' => [
                'eyebrow' => 'RYT200 瑜伽教培',
                'title' => '从热爱练习，到真正理解如何教学。',
                'sideEyebrow' => 'Info Session',
                'sideDescription' => '一麦 RYT200 以入学说明会与 1v1 职业适配评估作为前端入口，先确认学习目标、身体基础与未来方向，再推荐合适的训练路径。',
                'rightsEyebrow' => 'Training Rights',
                'rightsTitle' => '教培通用权益与跟练卡使用规则。',
                'supportEyebrow' => 'Yi Mai Support',
                'supportTitle' => '教培不是只拿证，而是把教学能力练出来。',
                'supportDescription' => '一麦会把训练、反馈、试讲与馆内机会拆成清晰路径，让学员更安全地从练习者走向表达者。',
                'complianceEyebrow' => 'Compliance Note',
                'complianceTitle' => '我们提供训练、反馈与机会评估，不承诺包就业、包开课或保底收入。',
                'complianceDescription' => '教学能力需要持续练习与真实反馈。一麦会基于学员阶段表现提供试讲、助教观察和人才池优先评估机会，最终发展路径以个人能力与馆内实际安排为准。',
            ],
        ],
        'courseThemes' => [
            [
                'title' => '肩颈理疗',
                'effect' => '缓解久坐紧张，改善肩颈僵硬与上背压力。',
                'suited' => '适合办公室人群、低头久坐、肩颈容易酸胀的会员。',
                'type' => '理疗修复',
                'image' => '/uploads/1778002143140-bab9e51e186a.jpg',
            ],
            [
                'title' => '灵活脊柱',
                'effect' => '提升脊柱活动度，改善身体僵硬和转动受限。',
                'suited' => '适合久坐、背部紧绷、想提升身体灵活度的人。',
                'type' => '体态唤醒',
                'image' => '/uploads/1778002152408-345fdb5e5a9ef8.jpg',
            ],
            [
                'title' => '普拉提核心床',
                'effect' => '强化核心控制、臀腿线条和身体稳定性。',
                'suited' => '适合塑形、产后恢复、核心薄弱和希望精细训练的会员。',
                'type' => '器械普拉提',
                'image' => '/uploads/1778002165860-56f5b7b0279d18.jpg',
            ],
            [
                'title' => '室内蹦极活力带',
                'effect' => '提升心肺、燃脂和身体协调，让训练更有趣。',
                'suited' => '适合喜欢活力课程、想提升代谢和运动兴趣的人。',
                'type' => '活力燃脂',
                'image' => '/uploads/1778002173766-ea11c111773978.jpg',
            ],
            [
                'title' => '空中瑜伽',
                'effect' => '借助吊床释放脊柱压力，建立身体控制与轻盈感。',
                'suited' => '适合想尝试特色课程、改善紧张和提升身体感知的会员。',
                'type' => '特色课程',
                'image' => '/uploads/1778002191771-f9ecf88a11a0c.jpg',
            ],
            [
                'title' => '墙绳瑜伽',
                'effect' => '借助墙绳辅助正位，帮助身体更安全地打开。',
                'suited' => '适合体态调整、柔韧不足、需要精准辅助的练习者。',
                'type' => '精准正位',
                'image' => '/uploads/1778002203377-a80bf577ed8a58.jpg',
            ],
            [
                'title' => '颂钵疗愈',
                'effect' => '通过声音振动放松神经系统，缓解压力与睡眠困扰。',
                'suited' => '适合压力大、睡眠浅、希望身心放松的人。',
                'type' => '身心疗愈',
                'image' => '/uploads/1778002210428-befbc7de39c5f.jpg',
            ],
            [
                'title' => '芳香瑜伽',
                'effect' => '结合呼吸、伸展与芳香体验，建立更温柔的练习节奏。',
                'suited' => '适合初学者、压力型人群和需要情绪舒缓的会员。',
                'type' => '舒缓放松',
                'image' => '/uploads/1778002228351-b1fe594e1cc16.jpg',
            ],
            [
                'title' => '椅子瑜伽',
                'effect' => '降低练习门槛，帮助安全进入伸展与力量训练。',
                'suited' => '适合初学者、柔韧较弱或需要低强度练习的人。',
                'type' => '友好入门',
                'image' => '/uploads/1778002237590-f3715ff8e98d88.jpg',
            ],
            [
                'title' => '内观流',
                'effect' => '在流动中建立专注、节奏与身体觉察。',
                'suited' => '适合有一定基础、喜欢音乐与流动感的练习者。',
                'type' => '流动觉察',
                'image' => '/uploads/1778002242292-d789d92a0dfdc8.jpg',
            ],
            [
                'title' => '阴瑜伽',
                'effect' => '深层放松筋膜与关节压力，改善紧张和疲劳感。',
                'suited' => '适合压力大、身体紧、需要慢节奏修复的人。',
                'type' => '深度放松',
                'image' => '/uploads/1778002247839-8293714cd7cba8.jpg',
            ],
            [
                'title' => '艾扬格 / 阿斯汤加',
                'effect' => '分别强调精准正位和力量序列，支持更深入的瑜伽进阶。',
                'suited' => '适合想深入瑜伽体系、提升力量和稳定性的会员。',
                'type' => '进阶练习',
                'image' => '/uploads/1778002256846-dbaf851d6a0fa8.jpg',
            ],
        ],
        'classPaths' => [
            [
                'title' => '定制私教',
                'description' => '以个人目标为中心，围绕体态、疼痛、塑形、孕产或康复需求制定方案。重点是专业评估、明确反馈和高效改变。',
                'tags' => [
                    '个人定制',
                    '效果导向',
                    '专业评估',
                    '高效陪跑',
                ],
            ],
            [
                'title' => '私教小班',
                'description' => '主题多、人数少、指导更精细。满 2 人即可开课，在小班氛围里获得接近私教级的动作修正和进阶反馈。',
                'tags' => [
                    '满2人开课',
                    '小人数',
                    '精准指导',
                    '主题丰富',
                ],
            ],
            [
                'title' => '精品团课',
                'description' => '排课丰富、氛围更强，覆盖内观流、阴瑜伽、维密燃脂、基础瑜伽等主题，适合建立稳定练习习惯。',
                'tags' => [
                    '排课多',
                    '氛围感',
                    '多时段',
                    '适合日常',
                ],
            ],
        ],
        'instructors' => [
            [
                'name' => '婷婷',
                'role' => '品牌主理人',
                'years' => '10年 / 10000+课时',
                'focus' => '瑜伽、空中、孕产、普拉提、颂钵疗愈',
                'image' => '/uploads/1778002272245-7f78817b6cb608.jpg',
                'summary' => '累计授课 10000+ 课时，长期深耕女性身心练习、空中体系、孕产修复、普拉提与疗愈课程。',
                'credentials' => [
                    'ACIC 国际认证高级瑜伽教练',
                    '空中瑜伽与空中艺术体系认证',
                    '高级孕产瑜伽导师',
                    '颂钵疗愈师 / 高级芳疗师',
                ],
                'specialties' => [
                    '品牌课程体系',
                    '空中瑜伽',
                    '孕产修复',
                    '颂钵疗愈',
                    '普拉提私教',
                ],
            ],
            [
                'name' => '渃兮',
                'role' => '东部店全职导师',
                'years' => '7年',
                'focus' => '瑜伽、普拉提、女子塑形、体态调整',
                'image' => '/uploads/1778002281134-373e4948759f4.jpg',
                'summary' => '擅长把瑜伽、普拉提与阻力训练结合，帮助会员建立更清晰的体态认知和线条目标。',
                'credentials' => [
                    '全美瑜伽联盟 RYT200 认证',
                    '空中瑜伽教练资格认证',
                    'ASTU 垫上普拉提 / 大器械认证',
                    'PRI 姿势恢复技术在学',
                ],
                'specialties' => [
                    '女子塑形',
                    '体态调整',
                    '普拉提大器械',
                    '抗阻训练',
                    '肩颈与脊柱管理',
                ],
            ],
            [
                'name' => '芷晴',
                'role' => '绿地店全职导师',
                'years' => '13年',
                'focus' => '基础瑜伽、哈他、内观流、孕产瑜伽',
                'image' => '/uploads/芷晴.jpg',
                'summary' => '长期专注精准练习、内观流与孕产主题课程，课程风格稳定、细腻，重视练习者的身体边界。',
                'credentials' => [
                    '全美瑜伽联盟 ERYT200 认证',
                    'Y+INSPYA 教师培训资格',
                    'INSIDE FLOW 内观流工作坊',
                    'Y+ 孕产教师培训证书',
                ],
                'specialties' => [
                    '基础瑜伽',
                    '哈他瑜伽',
                    '内观流',
                    '孕产瑜伽',
                    '阴瑜伽',
                ],
            ],
            [
                'name' => '黄敏',
                'role' => '绿地店全职导师',
                'years' => '10年',
                'focus' => '流瑜伽、孕产、颂钵疗愈、普拉提、维密塑形',
                'image' => '/uploads/1778002291956-35bc36fbf5b5c8.jpg',
                'summary' => '多体系综合型导师，覆盖瑜伽、普拉提、孕产、疗愈与塑形方向，适合需要综合身心管理的会员。',
                'credentials' => [
                    '全美瑜伽联盟 RYT500 注册老师',
                    'RPYT 孕产注册老师',
                    '尼泊尔颂钵疗愈师认证',
                    '北体大普拉提大器械认证',
                ],
                'specialties' => [
                    '元素流瑜伽',
                    '孕产瑜伽',
                    '颂钵疗愈',
                    '普拉提',
                    '维密塑形',
                ],
            ],
            [
                'name' => '娟子',
                'role' => '绿地店全职导师',
                'years' => '7年',
                'focus' => '体态调整、维密体雕、普拉提大器械',
                'image' => '/uploads/娟子.jpg',
                'summary' => '长期专注体态调整与体态康复，擅长结合普拉提大器械、功能性运动解剖与维密塑形，帮助会员改善体态、塑造线条。',
                'credentials' => [
                    '全美瑜伽联盟 RYT200 课程认证',
                    'MTFLY 功能性运动解剖工作坊',
                    'CPAL 体态美学架构师',
                    '亚洲运动导师联盟维密体雕培训',
                    '中国左右普拉提功能训练师',
                    'BNA 骨盆脊柱矫正训练师',
                    '加拿大脊柱健康学院运动疗法课程认证',
                ],
                'specialties' => [
                    '体态调整',
                    '体态康复',
                    '减脂塑形',
                    '维密体雕',
                    '哈他瑜伽',
                    '普拉提大器械',
                ],
            ],
            [
                'name' => '瑞文',
                'role' => '东部店全职导师',
                'years' => '14年',
                'focus' => '精准理疗、艾扬格、阿斯汤加、普拉提、运动康复',
                'image' => '/uploads/1778002306431-4a5bd291b358d8.jpg',
                'summary' => '14 年瑜伽教学经验，长期素食生活实践者，重视精准、稳定与身体觉察，适合希望深入练习的人群。',
                'credentials' => [
                    '香港亚太瑜伽联盟师资培训',
                    '哈他精准理疗课程认证',
                    '艾扬格瑜伽密集课程学习',
                    '普拉提运动康复课程认证',
                ],
                'specialties' => [
                    '精准理疗',
                    '艾扬格方向',
                    '阿斯汤加',
                    '能量流',
                    '普拉提康复',
                ],
            ],
        ],
        'studios' => [
            [
                'name' => '东部店',
                'area' => '600㎡',
                'address' => '宁波市鄞州区杉杉国贸天地D幢2楼',
                'phone' => '0574-87567277',
            ],
            [
                'name' => '绿地店',
                'area' => '500㎡',
                'address' => '宁波市江北区绿地缤纷城1号楼4楼',
                'phone' => '0574-87214863',
            ],
        ],
        'memberships' => [
            [
                'name' => '新人体验',
                'label' => '先了解身体',
                'feature' => '体态评估、体验课程与练习路径建议，适合第一次了解一麦的会员。',
                'accent' => '初次到店',
            ],
            [
                'name' => '精品团课',
                'label' => '高频日常练习',
                'feature' => '丰富主题与多时段排课，适合建立习惯、释放压力和保持稳定练习。',
                'accent' => '排课丰富',
            ],
            [
                'name' => '私教小班',
                'label' => '更强关注度',
                'feature' => '小人数练习氛围，兼顾老师关注、动作修正和阶段进阶。',
                'accent' => '精细指导',
            ],
            [
                'name' => '定制私教',
                'label' => '阶段目标管理',
                'feature' => '围绕体态、疼痛、塑形、孕产或康复需求，制定更个性化的练习方案。',
                'accent' => '一对一方案',
            ],
        ],
        'nav_items' => [
            [
                'label' => '首页',
                'href' => '/',
            ],
            [
                'label' => '课程',
                'href' => '/classes',
            ],
            [
                'label' => '师资',
                'href' => '/instructors',
            ],
            [
                'label' => '教培',
                'href' => '/training',
            ],
            [
                'label' => '预约',
                'href' => '/booking',
            ],
            [
                'label' => '会员',
                'href' => '/contact',
            ],
            [
                'label' => '博客',
                'href' => '/blog',
            ],
        ],
        'faqs' => [
            [
                '第一次来需要准备什么？',
                '穿着舒适运动服即可。普拉提大器械建议穿防滑袜，特殊课程会在课前单独提醒。',
            ],
            [
                '迟到或取消预约怎么办？',
                '建议提前一天预约。如需取消，请尽量在课程开始前 60 分钟联系门店。',
            ],
            [
                '孕期或产后可以练习吗？',
                '可以先预约咨询，老师会根据阶段、身体状态和医生建议判断适合的练习方式。',
            ],
            [
                '次卡和期限卡有什么区别？',
                '次卡按节数使用，更灵活；期限卡适合高频练习，在有效期内安排固定节奏。',
            ],
            [
                '第一次体验课多少钱？',
                '新人体验课通常有优惠价，具体以门店当季活动为准。可以在预约时说明是首次体验，老师会优先安排。',
            ],
            [
                '有适合零基础小白的课程吗？',
                '有。精品团课中的基础主题、私教小班和定制私教都适合零基础，老师会根据你的身体基础调整动作难度。',
            ],
            [
                '课程需要提前多久预约？',
                '团课建议提前一天在门店预约；私教课程与老师约好固定时间即可。热门时段建议尽早约。',
            ],
            [
                '可以带朋友一起来体验吗？',
                '可以。首次到店的朋友都可以预约体验课，一起练习更放松。',
            ],
        ],
        'training_audiences' => [
            '想从零开始系统学习瑜伽体系',
            '希望把兴趣练习升级为长期能力',
            '正在探索瑜伽副业或教学方向',
            '想获得更清晰职业适配建议的人',
        ],
        'training_programs' => [
            [
                'name' => '基础系统班',
                'price' => '10999元',
                'label' => 'RYT200 完整训练',
                'description' => '适合先系统完成 RYT200 教培，把体式、解剖、口令与基础教学能力扎实建立起来。',
                'points' => [
                    'RYT200 教培',
                    '教培专属跟练卡 20 节',
                ],
            ],
            [
                'name' => '专项成长班',
                'price' => '11999元',
                'label' => 'RYT200 + 空中专项',
                'description' => '适合想在 RYT200 基础上增加空中瑜伽专项技能，让未来课程方向更丰富的人。',
                'points' => [
                    'RYT200 教培',
                    '空中瑜伽初中级教培 3 天',
                    '教培专属跟练卡 20 节',
                ],
            ],
            [
                'name' => '高阶发展班',
                'price' => '13999元',
                'label' => '专项 + 深度支持',
                'description' => '适合想更深入训练、获得更多导师支持，并降低后续复训成本的进阶型学员。',
                'points' => [
                    'RYT200 教培',
                    '空中瑜伽初中级教培 3 天',
                    '复训场地费全免',
                    '导师私教 3 节',
                    '教培专属跟练卡 30 节',
                ],
            ],
        ],
        'announcements' => [
            // 首页弹窗总开关（关闭后首页不弹窗；预约页活动条仍随活动有无显示）
            'enabled' => true,
            'items' => [],
        ],
        'training_rights' => [
            '赠送专业瑜伽服 1 套',
            '一年内免费复训 1 次',
            '复训学费全免，基础系统班与专项成长班仅收场地费 1000 元',
            '教培跟练卡可预约团课和小班：团课每节扣 1 次，小班每节扣 2 次',
        ],
    ];

    // 数据库配置覆盖（后台 /admin 保存的 yimai_site_config）
    $db_raw = get_option('yimai_site_config', '');
    if ($db_raw) {
        $db_cfg = json_decode((string) $db_raw, true);
        if (is_array($db_cfg)) {
            return array_replace_recursive($default, $db_cfg);
        }
    }
    return $default;
}
