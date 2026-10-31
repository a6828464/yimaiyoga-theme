<?php
/**
 * 两店周课表（静态数据 + 归一化 / 过滤口径）
 *
 * 数据来源：门店提供的《东部店周课表.Xlsx》《绿地店周课表.Xlsx》（源表头 10.26–11.01 那一周），
 * 一次性转换成下面的 PHP 数组——运行时不读 xlsx、不发网络请求。改课表＝重新生成这一段数据。
 *
 * 每格结构：['time' => '11:15~12:15', 'course' => '核心床', 'teacher' => '渃兮',
 *            'room' => '普拉提教室', 'rating' => 2]，null = 该时段无课。
 * course 保留源表原样（含「(团课)/(精品课)」「丨备注」），归一化放在 yimai_timetable_clean_course()：
 * 原始数据不动，口径只在渲染层改一次，出问题能一条条对回原表。
 *
 * @package yimaiyoga
 */

if (!defined('ABSPATH')) {
    exit;
}

/** 原始课表：studio-east / studio-greenland（只有这两家店有课表） */
function yimai_studio_timetables(): array
{
    return [
        'studio-east' => [
            'week' => '10.26–11.01',
            'days' => ['周一10.26', '周二10.27', '周三10.28', '周四10.29', '周五10.30', '周六10.31', '周日11.01'],
            'rows' => [
                [
                    'start' => '11:15',
                    'slots' => [
                        ['time' => '11:15~12:15', 'course' => '核心床', 'teacher' => '渃兮', 'room' => '普拉提教室', 'rating' => 2],
                        ['time' => '11:15~12:15', 'course' => '垂直框架 直角肩锻造(精品课)', 'teacher' => '瑞文', 'room' => '普拉提教室', 'rating' => 2],
                        ['time' => '11:15~12:15', 'course' => '女性芳香', 'teacher' => '谭婷婷', 'room' => '墙绳教室', 'rating' => 2],
                        ['time' => '11:15~12:15', 'course' => '颂钵疗愈', 'teacher' => '韩燕', 'room' => '墙绳教室', 'rating' => 1],
                        ['time' => '11:15~12:15', 'course' => '空中瑜伽', 'teacher' => '韩燕', 'room' => '空中教室', 'rating' => 2],
                        null,
                        null,
                    ],
                ],
                [
                    'start' => '11:50',
                    'slots' => [
                        ['time' => '11:50~12:50', 'course' => '颂钵疗愈', 'teacher' => '韩燕', 'room' => '墙绳教室', 'rating' => 1],
                        ['time' => '11:50~12:50', 'course' => '核心床', 'teacher' => '嘉欣', 'room' => '普拉提教室', 'rating' => 3],
                        ['time' => '11:50~12:50', 'course' => '瑜伽', 'teacher' => '邱邱', 'room' => '墙绳教室', 'rating' => 2],
                        ['time' => '11:50~12:50', 'course' => '瑜伽', 'teacher' => '徐婷', 'room' => '空中教室', 'rating' => 2],
                        ['time' => '11:50~12:50', 'course' => '瑜伽', 'teacher' => '韩燕', 'room' => '墙绳教室', 'rating' => 2],
                        null,
                        null,
                    ],
                ],
                [
                    'start' => '12:00',
                    'slots' => [
                        ['time' => '12:00~13:00', 'course' => '核心床', 'teacher' => '瑞文', 'room' => '普拉提教室', 'rating' => 3],
                        ['time' => '12:00~13:00', 'course' => '核心床', 'teacher' => '诺一', 'room' => '普拉提教室', 'rating' => 3],
                        ['time' => '12:00~13:00', 'course' => '瑜伽', 'teacher' => 'Tina', 'room' => '墙绳教室', 'rating' => 2],
                        ['time' => '12:00~13:00', 'course' => '维密燃脂', 'teacher' => '管管', 'room' => '空中教室', 'rating' => 3],
                        ['time' => '12:00~13:00', 'course' => '核心床', 'teacher' => '诺一', 'room' => '普拉提教室', 'rating' => 2],
                        ['time' => '12:00~13:00', 'course' => '瑜伽', 'teacher' => '韩燕', 'room' => '墙绳教室', 'rating' => 2],
                        ['time' => '12:00~13:00', 'course' => '墙绳', 'teacher' => '围围', 'room' => '墙绳教室', 'rating' => 2],
                    ],
                ],
                [
                    'start' => '12:10',
                    'slots' => [
                        ['time' => '12:10~13:10', 'course' => '瑜伽', 'teacher' => '徐婷', 'room' => '空中教室', 'rating' => 2],
                        ['time' => '12:10~13:10', 'course' => '内观流(团课)', 'teacher' => '张芷晴', 'room' => '空中教室', 'rating' => 3],
                        ['time' => '12:10~13:10', 'course' => '软壶铃', 'teacher' => '余惜', 'room' => '墙绳教室', 'rating' => 3],
                        ['time' => '12:10~13:10', 'course' => '瑜伽', 'teacher' => '徐婷', 'room' => '墙绳教室', 'rating' => 2],
                        ['time' => '12:10~13:10', 'course' => '室内蹦极', 'teacher' => '苏米', 'room' => '空中教室', 'rating' => 3],
                        null,
                        null,
                    ],
                ],
                [
                    'start' => '12:20',
                    'slots' => [
                        ['time' => '12:20~13:20', 'course' => '核心床', 'teacher' => '嘉欣', 'room' => '普拉提教室', 'rating' => 3],
                        ['time' => '12:20~13:20', 'course' => '核心床', 'teacher' => '小唐', 'room' => '普拉提教室', 'rating' => 2],
                        ['time' => '12:20~13:20', 'course' => '核心床', 'teacher' => 'Tina', 'room' => '普拉提教室', 'rating' => 3],
                        ['time' => '12:20~13:20', 'course' => '根基与平衡(团课)', 'teacher' => '瑞文', 'room' => '墙绳教室', 'rating' => 2],
                        ['time' => '12:20~13:20', 'course' => '核心床', 'teacher' => '心怡', 'room' => '普拉提教室', 'rating' => 2],
                        null,
                        null,
                    ],
                ],
                [
                    'start' => '14:00',
                    'slots' => [
                        ['time' => '14:00~15:00', 'course' => '空中瑜伽', 'teacher' => '韩燕', 'room' => '空中教室', 'rating' => 2],
                        ['time' => '14:00~15:00', 'course' => '核心床', 'teacher' => '泽兴', 'room' => '普拉提教室', 'rating' => 2],
                        ['time' => '14:00~15:00', 'course' => '瑜伽', 'teacher' => '余惜', 'room' => '墙绳教室', 'rating' => 2],
                        ['time' => '14:00~15:00', 'course' => '脊柱保养(团课)', 'teacher' => '余惜', 'room' => '墙绳教室', 'rating' => 2],
                        ['time' => '14:00~15:00', 'course' => '波速球', 'teacher' => '余惜', 'room' => '空中教室', 'rating' => 3],
                        ['time' => '14:00~15:00', 'course' => '墙绳瑜伽(精品课)', 'teacher' => '瑞文', 'room' => '空中教室', 'rating' => 2],
                        ['time' => '14:00~15:00', 'course' => '核心床', 'teacher' => '心怡', 'room' => '普拉提教室', 'rating' => 3],
                    ],
                ],
                [
                    'start' => '16:10',
                    'slots' => [
                        ['time' => '16:10~17:10', 'course' => '核心床', 'teacher' => '心怡', 'room' => '普拉提教室', 'rating' => 3],
                        ['time' => '16:10~17:10', 'course' => '瑜伽', 'teacher' => '邱邱', 'room' => '墙绳教室', 'rating' => 3],
                        ['time' => '16:10~17:10', 'course' => '垂直框架', 'teacher' => '瑞文', 'room' => '普拉提教室', 'rating' => 3],
                        ['time' => '16:10~17:10', 'course' => '稳踏椅', 'teacher' => '管管', 'room' => '空中教室', 'rating' => 3],
                        ['time' => '16:10~17:10', 'course' => '瑜伽', 'teacher' => '渃兮', 'room' => '空中教室', 'rating' => 2],
                        null,
                        null,
                    ],
                ],
                [
                    'start' => '17:45',
                    'slots' => [
                        ['time' => '17:45~18:45', 'course' => '哈他瑜伽(团课)', 'teacher' => '瑞文', 'room' => '空中教室', 'rating' => 2],
                        ['time' => '17:45~18:45', 'course' => '女性芳香', 'teacher' => '谭婷婷', 'room' => '墙绳教室', 'rating' => 2],
                        ['time' => '17:45~18:45', 'course' => '颂钵疗愈', 'teacher' => '韩燕', 'room' => '墙绳教室', 'rating' => 1],
                        ['time' => '17:45~18:45', 'course' => '空中瑜伽', 'teacher' => '韩燕', 'room' => '空中教室', 'rating' => 2],
                        ['time' => '17:45~18:45', 'course' => '稳踏椅', 'teacher' => 'Nico', 'room' => '空中教室', 'rating' => 3],
                        ['time' => '17:45~18:45', 'course' => '开肩开髋(团课)', 'teacher' => '韩燕', 'room' => '空中教室', 'rating' => 2],
                        ['time' => '17:45~18:45', 'course' => '瑜伽', 'teacher' => '余惜', 'room' => '空中教室', 'rating' => 2],
                    ],
                ],
                [
                    'start' => '18:10',
                    'slots' => [
                        ['time' => '18:10~19:10', 'course' => '快乐燃脂操(精品课)', 'teacher' => 'CC', 'room' => '空中教室', 'rating' => 3],
                        ['time' => '18:10~19:10', 'course' => '哈他瑜伽(团课)', 'teacher' => '瑞文', 'room' => '空中教室', 'rating' => 2],
                        ['time' => '18:10~19:10', 'course' => '瑜伽', 'teacher' => '徐婷', 'room' => '空中教室', 'rating' => 2],
                        ['time' => '18:10~19:10', 'course' => '瑜伽', 'teacher' => '韩燕', 'room' => '墙绳教室', 'rating' => 2],
                        ['time' => '18:10~19:10', 'course' => '墙绳', 'teacher' => '围围', 'room' => '墙绳教室', 'rating' => 2],
                        ['time' => '18:10~19:10', 'course' => '核心床', 'teacher' => '小唐', 'room' => '普拉提教室', 'rating' => 2],
                        ['time' => '18:10~19:10', 'course' => '核心床', 'teacher' => 'Tina', 'room' => '普拉提教室', 'rating' => 3],
                    ],
                ],
                [
                    'start' => '18:10',
                    'slots' => [
                        ['time' => '18:10~19:10', 'course' => '瑜伽', 'teacher' => 'Tina', 'room' => '墙绳教室', 'rating' => 2],
                        ['time' => '18:10~19:10', 'course' => '瑜伽', 'teacher' => 'Tina', 'room' => '墙绳教室', 'rating' => 2],
                        ['time' => '18:10~19:10', 'course' => '维密燃脂', 'teacher' => '管管', 'room' => '空中教室', 'rating' => 3],
                        ['time' => '18:10~19:10', 'course' => '核心床', 'teacher' => '诺一', 'room' => '普拉提教室', 'rating' => 2],
                        ['time' => '18:10~19:10', 'course' => '室内蹦极', 'teacher' => '嘉欣', 'room' => '空中教室', 'rating' => 2],
                        null,
                        null,
                    ],
                ],
                [
                    'start' => '19:00',
                    'slots' => [
                        ['time' => '19:00~20:00', 'course' => '核心床', 'teacher' => '诺一', 'room' => '普拉提教室', 'rating' => 2],
                        ['time' => '19:00~20:00', 'course' => '软壶铃', 'teacher' => '余惜', 'room' => '墙绳教室', 'rating' => 3],
                        ['time' => '19:00~20:00', 'course' => '瑜伽', 'teacher' => '徐婷', 'room' => '墙绳教室', 'rating' => 2],
                        ['time' => '19:00~20:00', 'course' => '室内蹦极', 'teacher' => '苏米', 'room' => '空中教室', 'rating' => 3],
                        ['time' => '19:00~20:00', 'course' => '开肩开髋(团课)', 'teacher' => '韩燕', 'room' => '空中教室', 'rating' => 2],
                        null,
                        null,
                    ],
                ],
                [
                    'start' => '19:20',
                    'slots' => [
                        ['time' => '19:20~20:20', 'course' => '墙绳瑜伽(精品课)', 'teacher' => '瑞文', 'room' => '墙绳教室', 'rating' => 2],
                        ['time' => '19:20~20:20', 'course' => '核心床', 'teacher' => 'Tina', 'room' => '普拉提教室', 'rating' => 3],
                        ['time' => '19:20~20:20', 'course' => '根基与平衡(团课)', 'teacher' => '瑞文', 'room' => '墙绳教室', 'rating' => 2],
                        ['time' => '19:20~20:20', 'course' => '核心床', 'teacher' => '心怡', 'room' => '普拉提教室', 'rating' => 2],
                        ['time' => '19:20~20:20', 'course' => '核心床', 'teacher' => '瑞文', 'room' => '普拉提教室', 'rating' => 2],
                        null,
                        null,
                    ],
                ],
                [
                    'start' => '19:30',
                    'slots' => [
                        ['time' => '19:30~20:30', 'course' => '稳踏椅', 'teacher' => '管管', 'room' => '空中教室', 'rating' => 3],
                        ['time' => '19:30~20:30', 'course' => '瑜伽', 'teacher' => '余惜', 'room' => '墙绳教室', 'rating' => 2],
                        ['time' => '19:30~20:30', 'course' => '脊柱保养(团课)', 'teacher' => '余惜', 'room' => '墙绳教室', 'rating' => 2],
                        ['time' => '19:30~20:30', 'course' => '波速球', 'teacher' => '余惜', 'room' => '空中教室', 'rating' => 3],
                        ['time' => '19:30~20:30', 'course' => '瑜伽', 'teacher' => '余惜', 'room' => '墙绳教室', 'rating' => 2],
                        null,
                        null,
                    ],
                ],
            ],
        ],
        'studio-greenland' => [
            'week' => '10.26–11.01',
            'days' => ['周一10.26', '周二10.27', '周三10.28', '周四10.29', '周五10.30', '周六10.31', '周日11.01'],
            'rows' => [
                [
                    'start' => '09:00',
                    'slots' => [
                        ['time' => '09:00~10:00', 'course' => '核心床', 'teacher' => 'Tina', 'room' => 'VIP私教4', 'rating' => 3],
                        ['time' => '09:00~10:00', 'course' => '站立平衡-根基与控制(团课)', 'teacher' => '泽兴', 'room' => '空中教室', 'rating' => 3],
                        ['time' => '09:00~10:00', 'course' => 'ARC', 'teacher' => '钱冰璐', 'room' => 'VIP私教2', 'rating' => 3],
                        ['time' => '09:00~10:00', 'course' => '经典拜日(团课)', 'teacher' => '瑞文', 'room' => '空中教室', 'rating' => 2],
                        ['time' => '09:00~10:00', 'course' => '芳香疗愈', 'teacher' => '张芷晴', 'room' => 'VIP私教2', 'rating' => 1],
                        null,
                        null,
                    ],
                ],
                [
                    'start' => '10:30',
                    'slots' => [
                        ['time' => '10:30~11:30', 'course' => '芳香疗愈', 'teacher' => '张芷晴', 'room' => 'VIP私教2', 'rating' => 1],
                        ['time' => '10:30~11:30', 'course' => '稳踏椅', 'teacher' => '泽兴', 'room' => 'VIP私教2', 'rating' => 3],
                        ['time' => '10:30~11:30', 'course' => '核心床', 'teacher' => 'Nico', 'room' => 'VIP私教4', 'rating' => 3],
                        ['time' => '10:30~11:30', 'course' => '哈他瑜伽-基础综合(团课)', 'teacher' => '洪霞', 'room' => '墙绳教室', 'rating' => 2],
                        ['time' => '10:30~11:30', 'course' => '核心床', 'teacher' => '心怡', 'room' => 'VIP私教4', 'rating' => 3],
                        null,
                        null,
                    ],
                ],
                [
                    'start' => '12:00',
                    'slots' => [
                        ['time' => '12:00~13:00', 'course' => '核心床', 'teacher' => 'Nico', 'room' => 'VIP私教4', 'rating' => 3],
                        ['time' => '12:00~13:00', 'course' => '核心床', 'teacher' => 'Tina', 'room' => 'VIP私教4', 'rating' => 2],
                        ['time' => '12:00~13:00', 'course' => '舒缓瑜伽-释放一天疲惫(团课)', 'teacher' => '泽兴', 'room' => '墙绳教室', 'rating' => 1],
                        ['time' => '12:00~13:00', 'course' => '墙绳瑜伽(团课)', 'teacher' => '围围', 'room' => '墙绳教室', 'rating' => 2],
                        ['time' => '12:00~13:00', 'course' => '瑜伽小班', 'teacher' => '菲儿', 'room' => '墙绳教室', 'rating' => 2],
                        ['time' => '12:00~13:00', 'course' => '核心床', 'teacher' => '心怡', 'room' => 'VIP私教4', 'rating' => 3],
                        ['time' => '12:00~13:00', 'course' => '核心床', 'teacher' => '徐婷', 'room' => 'VIP私教4', 'rating' => 3],
                    ],
                ],
                [
                    'start' => '12:00',
                    'slots' => [
                        ['time' => '12:00~13:00', 'course' => '肩颈理疗-久坐解压(团课)', 'teacher' => '围围', 'room' => '墙绳教室', 'rating' => 1],
                        ['time' => '12:00~13:00', 'course' => '功能性训练-足弓与直腿(团课)', 'teacher' => '黄敏', 'room' => '空中教室', 'rating' => 2],
                        ['time' => '12:00~13:00', 'course' => '基础芭杆(团课)', 'teacher' => '泽兴', 'room' => '空中教室', 'rating' => 2],
                        ['time' => '12:00~13:00', 'course' => '维秘塑形-活力有氧(团课)', 'teacher' => '黄敏', 'room' => '空中教室', 'rating' => 3],
                        ['time' => '12:00~13:00', 'course' => '核心床', 'teacher' => '钱冰璐', 'room' => 'VIP私教4', 'rating' => 3],
                        null,
                        null,
                    ],
                ],
                [
                    'start' => '12:05',
                    'slots' => [
                        ['time' => '12:05~13:05', 'course' => '音乐普拉提(团课)', 'teacher' => '钱冰璐', 'room' => '空中教室', 'rating' => 3],
                        ['time' => '12:05~13:05', 'course' => '室内蹦极', 'teacher' => '苏米', 'room' => '空中教室', 'rating' => 2],
                        ['time' => '12:05~13:05', 'course' => '核心床', 'teacher' => '王小川', 'room' => 'VIP私教4', 'rating' => 3],
                        ['time' => '12:05~13:05', 'course' => '空中瑜伽', 'teacher' => '黄敏', 'room' => '空中教室', 'rating' => 3],
                        ['time' => '12:05~13:05', 'course' => '快乐燃脂操(团课)', 'teacher' => 'CC', 'room' => '空中教室', 'rating' => 2],
                        null,
                        null,
                    ],
                ],
                [
                    'start' => '14:30',
                    'slots' => [
                        ['time' => '14:30~15:30', 'course' => '核心床', 'teacher' => '钱冰璐', 'room' => 'VIP私教4', 'rating' => 3],
                        ['time' => '14:30~15:30', 'course' => '瑜伽小班', 'teacher' => '汐汐', 'room' => 'VIP私教2', 'rating' => 2],
                        ['time' => '14:30~15:30', 'course' => '脊柱流动-秋季舒缓(团课)', 'teacher' => '瑞秋', 'room' => '墙绳教室', 'rating' => 2],
                        ['time' => '14:30~15:30', 'course' => 'ARC腰腹塑型(团课)', 'teacher' => '钱冰璐', 'room' => '墙绳教室', 'rating' => 3],
                        ['time' => '14:30~15:30', 'course' => '椅子瑜伽丨下肢伸展(精品课)', 'teacher' => 'Tina', 'room' => 'VIP私教2', 'rating' => 2],
                        ['time' => '14:30~15:30', 'course' => '后弯流-核心与挑战(团课)', 'teacher' => '瑞秋', 'room' => '空中教室', 'rating' => 3],
                        ['time' => '14:30~15:30', 'course' => '内观流(团课)', 'teacher' => '张芷晴', 'room' => '空中教室', 'rating' => 3],
                    ],
                ],
                [
                    'start' => '16:30',
                    'slots' => [
                        ['time' => '16:30~17:30', 'course' => '瑜伽小班', 'teacher' => '菲儿', 'room' => '墙绳教室', 'rating' => 2],
                        ['time' => '16:30~17:30', 'course' => '阿斯汤伽(团课)', 'teacher' => '王小川', 'room' => '空中教室', 'rating' => 3],
                        ['time' => '16:30~17:30', 'course' => '核心床', 'teacher' => '瑞文', 'room' => 'VIP私教4', 'rating' => 3],
                        ['time' => '16:30~17:30', 'course' => '瑜伽-髋部理疗(团课)', 'teacher' => '洪霞', 'room' => '空中教室', 'rating' => 1],
                        ['time' => '16:30~17:30', 'course' => '核心床', 'teacher' => 'Tina', 'room' => 'VIP私教4', 'rating' => 3],
                        ['time' => '16:30~17:30', 'course' => '基础芭杆(团课)', 'teacher' => '泽兴', 'room' => '空中教室', 'rating' => 2],
                        ['time' => '16:30~17:30', 'course' => '维秘塑形-活力有氧(团课)', 'teacher' => '黄敏', 'room' => '空中教室', 'rating' => 3],
                    ],
                ],
                [
                    'start' => '18:00',
                    'slots' => [
                        ['time' => '18:00~19:00', 'course' => '核心床', 'teacher' => '徐婷', 'room' => 'VIP私教4', 'rating' => 3],
                        ['time' => '18:00~19:00', 'course' => '芳香疗愈', 'teacher' => '张芷晴', 'room' => 'VIP私教2', 'rating' => 1],
                        ['time' => '18:00~19:00', 'course' => '慢流瑜伽-温柔修整(团课)', 'teacher' => '汐汐', 'room' => '空中教室', 'rating' => 2],
                        ['time' => '18:00~19:00', 'course' => '普拉提圈', 'teacher' => 'Tina', 'room' => 'VIP私教2', 'rating' => 3],
                        ['time' => '18:00~19:00', 'course' => '内观流(团课)', 'teacher' => '汐汐', 'room' => '空中教室', 'rating' => 3],
                        ['time' => '18:00~19:00', 'course' => '波速球', 'teacher' => 'Nico', 'room' => '空中教室', 'rating' => 3],
                        ['time' => '18:00~19:00', 'course' => '核心床', 'teacher' => '黄敏', 'room' => 'VIP私教2', 'rating' => 3],
                    ],
                ],
                [
                    'start' => '18:00',
                    'slots' => [
                        ['time' => '18:00~19:00', 'course' => '快乐燃脂操(团课)', 'teacher' => 'CC', 'room' => '空中教室', 'rating' => 2],
                        ['time' => '18:00~19:00', 'course' => '核心床', 'teacher' => '心怡', 'room' => 'VIP私教4', 'rating' => 3],
                        ['time' => '18:00~19:00', 'course' => '核心床', 'teacher' => '徐婷', 'room' => 'VIP私教4', 'rating' => 3],
                        ['time' => '18:00~19:00', 'course' => '核心床', 'teacher' => '黄敏', 'room' => 'VIP私教4', 'rating' => 3],
                        ['time' => '18:00~19:00', 'course' => '肩颈理疗-久坐解压(团课)', 'teacher' => '围围', 'room' => '墙绳教室', 'rating' => 1],
                        null,
                        null,
                    ],
                ],
                [
                    'start' => '18:20',
                    'slots' => [
                        ['time' => '18:20~19:20', 'course' => '鸽王准备', 'teacher' => '瑞秋', 'room' => '墙绳教室', 'rating' => 3],
                        ['time' => '18:20~19:20', 'course' => '瑜伽小班', 'teacher' => '菲儿', 'room' => '墙绳教室', 'rating' => 2],
                        ['time' => '18:20~19:20', 'course' => '音乐普拉提(团课)', 'teacher' => '钱冰璐', 'room' => '空中教室', 'rating' => 3],
                        ['time' => '18:20~19:20', 'course' => '后弯流-核心与挑战(团课)', 'teacher' => '瑞秋', 'room' => '空中教室', 'rating' => 3],
                        ['time' => '18:20~19:20', 'course' => '内观流(团课)', 'teacher' => '张芷晴', 'room' => '空中教室', 'rating' => 3],
                        null,
                        null,
                    ],
                ],
                [
                    'start' => '19:10',
                    'slots' => [
                        ['time' => '19:10~20:10', 'course' => '哈他瑜伽-基础综合(团课)', 'teacher' => '徐婷', 'room' => '空中教室', 'rating' => 2],
                        ['time' => '19:10~20:10', 'course' => '核心床', 'teacher' => '钱冰璐', 'room' => 'VIP私教4', 'rating' => 3],
                        ['time' => '19:10~20:10', 'course' => '波速球', 'teacher' => 'Nico', 'room' => '空中教室', 'rating' => 3],
                        ['time' => '19:10~20:10', 'course' => '核心床', 'teacher' => '黄敏', 'room' => 'VIP私教2', 'rating' => 3],
                        ['time' => '19:10~20:10', 'course' => '内观流', 'teacher' => '张芷晴', 'room' => '空中教室', 'rating' => 2],
                        null,
                        null,
                    ],
                ],
                [
                    'start' => '19:30',
                    'slots' => [
                        ['time' => '19:30~20:30', 'course' => 'ARC', 'teacher' => '钱冰璐', 'room' => 'VIP私教2', 'rating' => 3],
                        ['time' => '19:30~20:30', 'course' => '快乐燃脂操(团课)', 'teacher' => 'CC', 'room' => '空中教室', 'rating' => 2],
                        ['time' => '19:30~20:30', 'course' => '核心流-腰腹稳定计划(团课)', 'teacher' => '瑞秋', 'room' => '墙绳教室', 'rating' => 3],
                        ['time' => '19:30~20:30', 'course' => '肩颈理疗-久坐解压(团课)', 'teacher' => '张芷晴', 'room' => '空中教室', 'rating' => 1],
                        ['time' => '19:30~20:30', 'course' => '鸽王准备', 'teacher' => '瑞秋', 'room' => '墙绳教室', 'rating' => 3],
                        null,
                        null,
                    ],
                ],
            ],
        ],
    ];
}

/**
 * 归一化课程名：拆出「(团课)/(精品课)」标签，去掉「｜」「丨」后面的备注。
 * '垂直框架 直角肩锻造(精品课)' → ['name' => '垂直框架 直角肩锻造', 'tag' => '精品课']
 * '椅子瑜伽丨下肢伸展(精品课)'  → ['name' => '椅子瑜伽', 'tag' => '精品课']
 *
 * @return array{name:string,tag:string}
 */
function yimai_timetable_clean_course(string $raw): array
{
    $text = trim($raw);
    $tag = '';
    if (preg_match('/[（(]\s*(团课|精品课)\s*[)）]\s*$/u', $text, $m)) {
        $tag = $m[1];
        $text = trim(mb_substr($text, 0, mb_strlen($text) - mb_strlen($m[0])));
    }
    $parts = preg_split('/[｜丨]/u', $text);
    $name = trim((string) ($parts[0] ?? ''));
    if ($name === '') {
        $name = trim($text);
    }
    return ['name' => $name, 'tag' => $tag];
}

/**
 * 非对外课程：保洁日、内部训练、企业包课都不对外招生，不出现在官网课表上
 * （会员看到「月初清洁日」占着黄金时段，只会以为课卖完了；
 *   看到「企业课定制」则以为自己也能报名，到店才发现是包场）。
 */
function yimai_timetable_is_internal(string $name): bool
{
    foreach (['月初清洁日', '月初启新清洁日', '内部训练', '内部提升', '企业课定制'] as $prefix) {
        if (str_starts_with($name, $prefix)) {
            return true;
        }
    }
    return false;
}

/**
 * 课程 → 固定色点 class（同一门课永远同一个颜色；不同课允许撞色）。
 * 用 crc32 而不是数组下标：后台/源表增删课程时，其它课的颜色不会整体错位。
 */
function yimai_timetable_dot_class(string $name): string
{
    $palette = ['a', 'b', 'c', 'd', 'e', 'f'];
    return 'tt-dot--' . $palette[crc32($name) % count($palette)];
}

/**
 * 星期标签归一：'周一10.26' / '周一' → '周一'；不足 7 个时补默认周一…周日。
 *
 * @return array<int, string>
 */
function yimai_timetable_day_labels(array $days): array
{
    $labels = [];
    foreach ($days as $label) {
        $label = trim((string) $label);
        if ($label === '') {
            continue;
        }
        $labels[] = preg_match('/^(周[一二三四五六日天])/u', $label, $m) ? $m[1] : $label;
    }
    $fallback = ['周一', '周二', '周三', '周四', '周五', '周六', '周日'];
    return count($labels) === 7 ? $labels : $fallback;
}

/**
 * 开始时间 → 时段字符串（源表全是 60 分钟课）：'11:15' → '11:15~12:15'。
 * 后台只填开始时间，结束时间由这里算，避免两个框对不上。
 */
function yimai_timetable_slot_time(string $start): string
{
    $start = trim($start);
    if (!preg_match('/^([01]?\d|2[0-3]):([0-5]\d)$/', $start, $m)) {
        return $start;
    }
    $start = sprintf('%02d:%02d', (int) $m[1], (int) $m[2]);
    $endMinutes = ((int) $m[1]) * 60 + (int) $m[2] + 60;
    $endMinutes %= 24 * 60;
    return $start . '~' . sprintf('%02d:%02d', intdiv($endMinutes, 60), $endMinutes % 60);
}

/**
 * 课表数据源：**后台配置优先，文件兜底**。
 * 1) config['timetables'][$key]（后台「门店课表」保存的）结构合法且有行 → 用它；
 * 2) 否则回落 inc/studio-timetables.php 里的默认数据（迁移基线，不再要求改文件）。
 * 配置里是坏数据（类型不对/没有 rows）时同样回落，前台不会白屏。
 */
function yimai_timetable_source(string $key): array
{
    if (function_exists('yimai_config')) {
        $saved = yimai_config()['timetables'][$key] ?? null;
        // 有 rows 键就认（哪怕是空数组）：空表 = 该店有意不显示课表，不能再回落成旧默认，
        // 否则「我把课表删空了，前台还挂着上一版」——这类「改了没生效」最难解释。
        if (is_array($saved) && isset($saved['rows']) && is_array($saved['rows'])) {
            return $saved;
        }
    }
    $all = yimai_studio_timetables();
    return (isset($all[$key]) && is_array($all[$key])) ? $all[$key] : [];
}

/**
 * 后台保存前的结构清洗与校验（不写库，只返回结果）。
 * 规则：周次任意文本；星期必须 7 个；每行 start 必须 HH:MM；每行必须 7 个格子；
 * 格子 course 留空 → null（老师/教室/星级一并清空，不留只有老师的脏格）；
 * 星级 0–5 越界夹紧；行数 0–40。
 *
 * @return array{ok:bool,message:string,timetables:array<string,array>}
 */
function yimai_timetable_sanitize_config(array $posted): array
{
    $known = ['studio-east', 'studio-greenland'];
    $out = [];
    foreach ($posted as $key => $store) {
        if (!in_array((string) $key, $known, true)) {
            continue; // 只认这两家店，别的键忽略
        }
        if (!is_array($store)) {
            return ['ok' => false, 'message' => $key . ' 的结构不对（应为对象）', 'timetables' => []];
        }
        $rowsIn = $store['rows'] ?? [];
        if (!is_array($rowsIn) || (array_is_list($rowsIn) === false)) {
            return ['ok' => false, 'message' => $key . ' 的 rows 应为列表', 'timetables' => []];
        }
        if (count($rowsIn) > 40) {
            return ['ok' => false, 'message' => $key . ' 的行数超过 40，先删掉多余时段', 'timetables' => []];
        }
        $rows = [];
        foreach (array_values($rowsIn) as $i => $row) {
            $rowNo = $i + 1;
            if (!is_array($row)) {
                return ['ok' => false, 'message' => $key . ' 第 ' . $rowNo . ' 行结构不对', 'timetables' => []];
            }
            $start = trim((string) ($row['start'] ?? ''));
            if (!preg_match('/^([01]?\d|2[0-3]):([0-5]\d)$/', $start)) {
                return ['ok' => false, 'message' => $key . ' 第 ' . $rowNo . ' 行开始时间应写成 HH:MM（如 09:00）', 'timetables' => []];
            }
            [$startHour, $startMinute] = array_map('intval', explode(':', $start));
            $start = sprintf('%02d:%02d', $startHour, $startMinute);
            $slotsIn = $row['slots'] ?? [];
            if (!is_array($slotsIn) || count($slotsIn) !== 7) {
                return ['ok' => false, 'message' => $key . ' 第 ' . $rowNo . ' 行应有 7 个格子（周一到周日）', 'timetables' => []];
            }
            $slots = [];
            foreach (array_values($slotsIn) as $slot) {
                if (!is_array($slot)) {
                    $slots[] = null;
                    continue;
                }
                $course = trim((string) ($slot['course'] ?? ''));
                if ($course === '') {
                    $slots[] = null; // 课程为空即无课：老师/教室/星级一并清掉
                    continue;
                }
                $rating = $slot['rating'] ?? 0;
                $rating = is_numeric($rating) ? (int) $rating : 0;
                $slots[] = [
                    'course'  => mb_substr($course, 0, 60),
                    'teacher' => mb_substr(trim((string) ($slot['teacher'] ?? '')), 0, 30),
                    'room'    => mb_substr(trim((string) ($slot['room'] ?? '')), 0, 30),
                    'rating'  => max(0, min(5, $rating)),
                ];
            }
            $rows[] = ['start' => $start, 'slots' => $slots];
        }
        $days = yimai_timetable_day_labels(is_array($store['days'] ?? null) ? $store['days'] : []);
        $out[(string) $key] = [
            'week' => mb_substr(trim((string) ($store['week'] ?? '')), 0, 40),
            'days' => $days,
            'rows' => $rows,
        ];
    }
    if ($out === []) {
        return ['ok' => false, 'message' => '没有收到任何门店课表', 'timetables' => []];
    }
    return ['ok' => true, 'message' => '', 'timetables' => $out];
}

/**
/**
 * 渲染用课表：归一化 + 过滤非对外课程 + 丢掉过滤后整行无课的时段。
 * 未知门店或过滤后没有课，返回空的 rows（调用方据此整块不渲染）。
 *
 * @return array{days:array<int,string>,rows:array<int,array{start:string,slots:array<int,mixed>}>,row_count:int,course_count:int,courses:array<int,string>}
 */
function yimai_studio_timetable(string $key): array
{
    $empty = ['days' => [], 'week' => '', 'rows' => [], 'row_count' => 0, 'course_count' => 0, 'courses' => []];
    // 后台配置优先、文件兜底；两边的数据在这里走同一套归一化，前台表现一致。
    $source = yimai_timetable_source($key);
    if ($source === [] || !isset($source['rows'])) {
        return $empty;
    }
    // 表头只留「周一…周日」：静态课表带死日期，过一周就成了「上周的课表」，比不带更糟。
    // 周期（10.26–11.01）只出现一次，交给模板的 caption 与说明行。
    $days = yimai_timetable_day_labels((array) ($source['days'] ?? []));
    $rows = [];
    $courses = [];
    $classCount = 0;
    foreach ((array) $source['rows'] as $row) {
        if (!is_array($row)) {
            continue;
        }
        $slots = [];
        $hasClass = false;
        foreach ((array) ($row['slots'] ?? []) as $slot) {
            if (!is_array($slot)) {
                $slots[] = null;
                continue;
            }
            $clean = yimai_timetable_clean_course((string) ($slot['course'] ?? ''));
            if ($clean['name'] === '' || yimai_timetable_is_internal($clean['name'])) {
                $slots[] = null;
                continue;
            }
            $hasClass = true;
            $classCount++;
            $courses[$clean['name']] = true;
            // 课程名带营销后缀时（'慢流瑜伽-温柔修整'），手机上整段会把格子撑到 3 行。
            // 渲染层切出「主干 + 后缀」：<=700px 只显示主干，全名仍在 title/aria-label 与 DOM 文本里。
            $trunk = $clean['name'];
            $extra = '';
            if (preg_match('/^([^-—－]+)[-—－].+$/u', $clean['name'], $cut) && trim($cut[1]) !== '') {
                $trunk = trim($cut[1]);
                $extra = mb_substr($clean['name'], mb_strlen($cut[0]) - mb_strlen(mb_substr($clean['name'], 0, mb_strlen($cut[0]))));
                $extra = mb_substr($clean['name'], mb_strlen($trunk)); // 含分隔符，DOM 文本拼回去等于全名
            }
            $slotTime = trim((string) ($slot['time'] ?? ''));
            if ($slotTime === '') {
                // 后台保存的数据只有 start：结束时间按 +60 分钟生成（与后台一致的唯一口径）
                $slotTime = yimai_timetable_slot_time((string) ($row['start'] ?? ''));
            }
            $slots[] = [
                'time'    => $slotTime,
                'course'  => $clean['name'],
                'trunk'   => $trunk,
                'extra'   => $extra,
                'tag'     => $clean['tag'],
                'teacher' => (string) ($slot['teacher'] ?? ''),
                'room'    => (string) ($slot['room'] ?? ''),
                'rating'  => max(0, min(5, (int) ($slot['rating'] ?? 0))),
                'dot'     => yimai_timetable_dot_class($clean['name']),
            ];
        }
        if (!$hasClass) {
            continue; // 过滤后整行无课：这一行在官网没有信息量
        }
        $rows[] = ['start' => (string) ($row['start'] ?? ''), 'slots' => $slots];
    }
    return [
        'days'         => $days,
        'week'         => trim((string) ($source['week'] ?? '')),
        'rows'         => $rows,
        'row_count'    => count($rows),
        'course_count' => $classCount,
        'courses'      => array_keys($courses),
    ];
}
