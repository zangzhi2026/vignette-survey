<?php
/**
 * CSV 导出 - 完整实验数据
 */

require_once __DIR__ . '/../index.php';

$survey_id = $_GET['id'] ?? '';
if (empty($survey_id)) {
    die('问卷ID不能为空');
}

$surveys = get_surveys();
$survey = null;
foreach ($surveys as $s) {
    if ($s['id'] === $survey_id) {
        $survey = $s;
        break;
    }
}

if (!$survey) {
    die('问卷不存在');
}

$responses = get_responses($survey_id);
if (empty($responses)) {
    die('暂无数据');
}

// CSV 设置
$delimiter = ',';
$enclosure = '"';

// 表头
$headers = [
    '编号', '分组', '提交时间',
    // 操纵检验
    'MC1_自主规划', 'MC2_自主判断', 'MC3_决策权', 'MC4_主导权',
    // 预期压力
    'STR1_精疲力竭', 'STR2_疲惫', 'STR3_意义怀疑', 'STR4_冷漠',
    // 技术压力
    'TECH1_验证输出', 'TECH2_跟上AI', 'TECH3_紧张焦虑', 'TECH4_技能过时',
    // 控制感
    'CTRL1_无掌控', 'CTRL2_非我做主', 'CTRL3_执行安排',
    // 真实性 + 注意力
    'REALISM_真实性', 'ATTENTION_注意力',
    // 人口学
    '性别', '年龄', '学历', 'AI使用经验',
    // 耗时
    '总用时_秒'
];

// 输出 BOM
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $survey['title'] . '_数据导出_' . date('Ymd') . '.csv"');
echo "\xEF\xBB\xBF";

// 表头行
$line = [];
foreach ($headers as $h) {
    $line[] = $enclosure . str_replace($enclosure, $enclosure . $enclosure, $h) . $enclosure;
}
echo implode($delimiter, $line) . "\n";

// 数据行
foreach ($responses as $r) {
    $row = [];
    
    // 编号
    $row[] = $enclosure . ($r['participant_id'] ?? '') . $enclosure;
    
    // 分组 (low=0, high=1)
    $group = $r['group'] ?? '';
    $groupNum = $group === 'low' ? '0' : ($group === 'high' ? '1' : '');
    $row[] = $enclosure . $groupNum . $enclosure;
    
    // 提交时间
    $submitted_at = isset($r['submitted_at']) ? date('Y-m-d H:i:s', $r['submitted_at'] / 1000) : '';
    $row[] = $enclosure . $submitted_at . $enclosure;
    
    // 操纵检验 (4题)
    $mc = $r['manipulation_check'] ?? [];
    for ($i = 0; $i < 4; $i++) {
        $row[] = $enclosure . ($mc[$i] ?? '') . $enclosure;
    }
    
    // 预期压力 (4题)
    $stress = $r['anticipated_stress'] ?? [];
    for ($i = 0; $i < 4; $i++) {
        $row[] = $enclosure . ($stress[$i] ?? '') . $enclosure;
    }
    
    // 技术压力 (4题)
    $tech = $r['tech_anxiety'] ?? [];
    for ($i = 0; $i < 4; $i++) {
        $row[] = $enclosure . ($tech[$i] ?? '') . $enclosure;
    }
    
    // 控制感 (3题)
    $ctrl = $r['loss_of_control'] ?? [];
    for ($i = 0; $i < 3; $i++) {
        $row[] = $enclosure . ($ctrl[$i] ?? '') . $enclosure;
    }
    
    // 真实性和注意力
    $row[] = $enclosure . ($r['realism'] ?? '') . $enclosure;
    $row[] = $enclosure . ($r['attention_check'] ?? '') . $enclosure;
    
    // 人口学
    $demo = $r['demographics'] ?? [];
    $row[] = $enclosure . ($demo['gender'] ?? '') . $enclosure;
    $row[] = $enclosure . ($demo['age'] ?? '') . $enclosure;
    $row[] = $enclosure . ($demo['education'] ?? '') . $enclosure;
    $row[] = $enclosure . ($demo['ai_usage'] ?? '') . $enclosure;
    
    // 总用时
    $row[] = $enclosure . ($r['total_time'] ?? '') . $enclosure;
    
    echo implode($delimiter, $row) . "\n";
}

exit;