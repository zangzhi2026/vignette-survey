<?php
/**
 * 管理后台 - 实验问卷配置
 */

require_once __DIR__ . '/../index.php';

if (check_admin_login() !== true) {
    $login_error = check_admin_login();
    check_admin_login(); // 显示登录页
}
check_admin_login();

$surveys = get_surveys();

// 处理删除
if (isset($_GET['delete'])) {
    $surveys = array_filter($surveys, fn($s) => $s['id'] !== $_GET['delete']);
    save_surveys(array_values($surveys));
    header('Location: ?action=admin');
    exit;
}

// 获取要编辑的问卷
$edit_survey = null;
if (isset($_GET['edit'])) {
    $edit_id = $_GET['edit'];
    if ($edit_id !== 'new') {
        foreach ($surveys as $s) {
            if ($s['id'] === $edit_id) {
                $edit_survey = $s;
                break;
            }
        }
    }
}

// 获取各问卷的响应数
$response_counts = ['low' => 0, 'high' => 0];
$survey_stats = [];
foreach ($surveys as $s) {
    $responses = get_responses($s['id']);
    $stats = ['total' => 0, 'low' => 0, 'high' => 0];
    foreach ($responses as $r) {
        $stats['total']++;
        $group = $r['group'] ?? 'unknown';
        if ($group === 'low') $stats['low']++;
        elseif ($group === 'high') $stats['high']++;
    }
    $survey_stats[$s['id']] = $stats;
}
?>
<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理后台 - Vignette 实验问卷系统</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; 
            background: #f0f2f5;
            color: #333;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .header h1 { font-size: 22px; }
        .header a { color: white; text-decoration: none; opacity: 0.9; }
        .header a:hover { opacity: 1; }
        .container { max-width: 1100px; margin: 30px auto; padding: 0 20px; }
        
        .toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }
        .btn {
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(102,126,234,0.4); }
        .btn-secondary { background: #e8e8e8; color: #666; }
        .btn-secondary:hover { background: #ddd; }
        .btn-danger { background: #e74c3c; color: white; }
        
        .survey-list { display: flex; flex-direction: column; gap: 16px; }
        .survey-card {
            background: white;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        .survey-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 16px; }
        .survey-title { font-size: 18px; font-weight: 600; color: #333; }
        .survey-meta { color: #888; font-size: 13px; margin-top: 4px; }
        .survey-stats { display: flex; gap: 24px; margin: 16px 0; padding: 16px; background: #f8f9fa; border-radius: 8px; }
        .stat-item { text-align: center; }
        .stat-value { font-size: 24px; font-weight: 700; color: #667eea; }
        .stat-label { font-size: 12px; color: #888; margin-top: 4px; }
        .stat-group { display: flex; gap: 16px; }
        .stat-group .stat-item { display: flex; flex-direction: column; align-items: center; }
        .stat-group .stat-value { font-size: 18px; }
        
        .actions { display: flex; gap: 8px; margin-top: 16px; }
        .btn-sm { padding: 6px 12px; font-size: 12px; }
        
        .empty-state {
            text-align: center;
            padding: 60px;
            background: white;
            border-radius: 12px;
        }
        .empty-state p { color: #888; font-size: 16px; }
        
        /* 编辑表单 */
        .edit-container { background: white; border-radius: 12px; padding: 30px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
        .back-link { margin-bottom: 20px; }
        .back-link a { color: #667eea; text-decoration: none; font-size: 14px; }
        
        .form-section {
            margin-bottom: 30px;
            padding-bottom: 30px;
            border-bottom: 1px solid #eee;
        }
        .form-section:last-child { border-bottom: none; }
        .form-section h3 {
            font-size: 16px;
            color: #667eea;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .form-group { margin-bottom: 20px; }
        .form-group label {
            display: block;
            font-weight: 500;
            margin-bottom: 8px;
            color: #333;
        }
        .form-group input, .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e8e8e8;
            border-radius: 8px;
            font-size: 14px;
            font-family: inherit;
            transition: border-color 0.2s;
        }
        .form-group input:focus, .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
        }
        .form-group textarea { min-height: 100px; resize: vertical; }
        .form-hint { font-size: 12px; color: #888; margin-top: 6px; }
        
        .scenario-box {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 20px;
            margin-top: 12px;
        }
        .scenario-box.low { border-left: 4px solid #27ae60; }
        .scenario-box.high { border-left: 4px solid #e74c3c; }
        .scenario-box label { font-weight: 600; color: #555; display: block; margin-bottom: 10px; }
        
        .button-group { display: flex; gap: 12px; margin-top: 30px; }
        
        /* 预览标签 */
        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }
        .badge-low { background: #d4edda; color: #155724; }
        .badge-high { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
    <div class="header">
        <h1>⚙️ 管理后台</h1>
        <a href="?action=home">← 返回首页</a>
    </div>
    
    <div class="container">
        <?php if (isset($_GET['edit'])): ?>
        <!-- 编辑问卷 -->
        <div class="edit-container">
            <div class="back-link">
                <a href="?action=admin">← 返回问卷列表</a>
            </div>
            
            <h2 style="margin-bottom: 30px; font-size: 20px;"><?php echo $edit_survey ? '编辑实验问卷' : '创建新实验问卷'; ?></h2>
            
            <form id="surveyForm">
                <input type="hidden" name="id" value="<?php echo htmlspecialchars($edit_survey['id'] ?? ''); ?>">
                
                <div class="form-section">
                    <h3>📋 基本信息</h3>
                    
                    <div class="form-group">
                        <label>问卷标题 *</label>
                        <input type="text" name="title" required value="<?php echo htmlspecialchars($edit_survey['title'] ?? 'AI自主程度对员工倦怠的影响'); ?>">
                        <p class="form-hint">被试在知情同意页会看到这个标题</p>
                    </div>
                    
                    <div class="form-group">
                        <label>问卷说明</label>
                        <textarea name="description"><?php echo htmlspecialchars($edit_survey['description'] ?? '本研究关注员工在与不同自主程度的人工智能系统协作时的心理感受。'); ?></textarea>
                        <p class="form-hint">在知情同意页显示，可以解释研究目的</p>
                    </div>
                </div>
                
                <div class="form-section">
                    <h3>📝 情境材料配置</h3>
                    <p style="color: #888; font-size: 13px; margin-bottom: 16px;">请按手册原文填写，两种情境只在"谁做决策"上不同，字数和描述要尽量一致。</p>
                    
                    <div class="form-group">
                        <label>共同开头（两种情境都先显示这段）</label>
                        <textarea name="common_intro">请想象以下情境，并尽量代入这位员工的处境作答。您是一家公司的业务专员，日常工作包括处理客户需求、制定工作方案、推进项目执行。最近，公司在您的岗位上全面引入了一套人工智能系统，您每天的工作都需要与它配合完成。</textarea>
                        <?php if ($edit_survey): ?>
                        <script>document.querySelector('[name="common_intro"]').value = <?php echo json_encode($edit_survey['common_intro'] ?? ''); ?>;</script>
                        <?php endif; ?>
                    </div>
                    
                    <div class="scenario-box low">
                        <label>低自主版本（AI作辅助、人做主）</label>
                        <textarea name="scenario_low" placeholder="这套AI系统的角色是辅助您。当您处理任务时，它会分析数据、提出若干建议方案，并标注各方案的利弊。最终采用哪个方案、如何调整、是否推进，都由您自己判断和决定。AI负责提供信息和选项，您始终掌握工作流程的决策权和主导权。"><?php echo htmlspecialchars($edit_survey['scenario_low'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="scenario-box high">
                        <label>高自主版本（AI做主、人配合）</label>
                        <textarea name="scenario_high" placeholder="这套AI系统的角色是主导您的工作。当任务进入系统后，它会自主分析、自主选定方案并直接安排执行，通常无需您的事先确认。采用哪个方案、如何推进，主要由AI判断和决定，您的工作是配合系统、执行它安排的环节。AI掌握工作流程的决策权和主导权。"><?php echo htmlspecialchars($edit_survey['scenario_high'] ?? ''); ?></textarea>
                    </div>
                </div>
                
                <div class="form-section">
                    <h3>👤 人口学字段配置</h3>
                    <p style="color: #888; font-size: 13px; margin-bottom: 16px;">选择要收集的人口学信息（实验通常需要性别、年龄、学历、AI使用经验）</p>
                    
                    <div class="form-group">
                        <label>启用的人口学字段</label>
                        <div style="display: flex; flex-wrap: wrap; gap: 10px; margin-top: 10px;">
                            <?php 
                            $demo_options = [
                                'gender' => '性别',
                                'age' => '年龄',
                                'education' => '学历',
                                'ai_usage' => 'AI使用经验'
                            ];
                            $selected_demo = $edit_survey['demographics'] ?? ['gender', 'age', 'education', 'ai_usage'];
                            foreach ($demo_options as $key => $label): 
                            ?>
                            <label class="checkbox-chip <?php echo in_array($key, $selected_demo) ? 'checked' : ''; ?>" style="display: flex; align-items: center; gap: 6px; padding: 10px 16px; background: #f0f0f0; border-radius: 20px; cursor: pointer; transition: all 0.2s;">
                                <input type="checkbox" name="demographics[]" value="<?php echo $key; ?>" <?php echo in_array($key, $selected_demo) ? 'checked' : ''; ?> style="display: none;">
                                <span><?php echo $label; ?></span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                
                <div class="button-group">
                    <button type="submit" class="btn btn-primary">保存问卷</button>
                    <a href="?action=admin" class="btn btn-secondary">取消</a>
                </div>
            </form>
        </div>
        
        <script>
        // 复选框样式切换
        document.querySelectorAll('.checkbox-chip').forEach(chip => {
            chip.addEventListener('click', (e) => {
                if (e.target.tagName === 'INPUT') return;
                const input = chip.querySelector('input');
                input.checked = !input.checked;
                chip.classList.toggle('checked', input.checked);
                chip.style.background = input.checked ? '#667eea' : '#f0f0f0';
                chip.style.color = input.checked ? 'white' : '#333';
            });
            // 初始化样式
            const input = chip.querySelector('input');
            if (input.checked) {
                chip.classList.add('checked');
                chip.style.background = '#667eea';
                chip.style.color = 'white';
            }
        });
        
        // 表单提交
        document.getElementById('surveyForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const form = e.target;
            const formData = new FormData(form);
            
            const data = {
                id: formData.get('id') || 'exp_' + Date.now(),
                title: formData.get('title'),
                description: formData.get('description'),
                common_intro: formData.get('common_intro'),
                scenario_low: formData.get('scenario_low'),
                scenario_high: formData.get('scenario_high'),
                demographics: formData.getAll('demographics[]'),
                created_at: new Date().toISOString().split('T')[0]
            };
            
            if (!data.scenario_low || !data.scenario_high) {
                alert('请填写两种情境材料');
                return;
            }
            
            try {
                const res = await fetch('?action=api&method=saveSurvey', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify(data)
                });
                
                if (res.ok) {
                    window.location.href = '?action=admin';
                } else {
                    const err = await res.json();
                    alert('保存失败: ' + (err.error || '未知错误'));
                }
            } catch (err) {
                alert('保存失败: ' + err.message);
            }
        });
        </script>
        
        <?php else: ?>
        <!-- 问卷列表 -->
        <div class="toolbar">
            <h2 style="font-size: 20px;">📋 实验问卷管理</h2>
            <a href="?action=admin&edit=new" class="btn btn-primary">+ 创建新问卷</a>
        </div>
        
        <?php if (empty($surveys)): ?>
        <div class="empty-state">
            <p style="font-size: 48px; margin-bottom: 16px;">📝</p>
            <p>还没有实验问卷，点击上方按钮创建</p>
        </div>
        <?php else: ?>
        <div class="survey-list">
            <?php foreach ($surveys as $survey): ?>
            <?php $stats = $survey_stats[$survey['id']] ?? ['total' => 0, 'low' => 0, 'high' => 0]; ?>
            <div class="survey-card">
                <div class="survey-header">
                    <div>
                        <div class="survey-title"><?php echo htmlspecialchars($survey['title']); ?></div>
                        <div class="survey-meta">
                            <span class="badge badge-low">低自主 <?php echo $stats['low']; ?> 份</span>
                            <span class="badge badge-high">高自主 <?php echo $stats['high']; ?> 份</span>
                        </div>
                    </div>
                </div>
                
                <div class="survey-stats">
                    <div class="stat-item">
                        <div class="stat-value"><?php echo $stats['total']; ?></div>
                        <div class="stat-label">总收集</div>
                    </div>
                    <div class="stat-group">
                        <div class="stat-item">
                            <div class="stat-value" style="color: #27ae60;"><?php echo $stats['low']; ?></div>
                            <div class="stat-label">低自主组</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value" style="color: #e74c3c;"><?php echo $stats['high']; ?></div>
                            <div class="stat-label">高自主组</div>
                        </div>
                    </div>
                </div>
                
                <div class="actions">
                    <a href="?action=admin&edit=<?php echo urlencode($survey['id']); ?>" class="btn btn-secondary btn-sm">编辑</a>
                    <a href="?action=survey&id=<?php echo urlencode($survey['id']); ?>" target="_blank" class="btn btn-secondary btn-sm">预览</a>
                    <a href="?action=export&id=<?php echo urlencode($survey['id']); ?>" class="btn btn-secondary btn-sm">导出CSV</a>
                    <a href="?action=admin&delete=<?php echo urlencode($survey['id']); ?>" class="btn btn-danger btn-sm" onclick="return confirm('确定删除？所有数据将被清除。')">删除</a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</body>
</html>