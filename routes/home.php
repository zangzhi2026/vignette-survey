<?php
/**
 * 首页
 */

$surveys = get_surveys();
?>
<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vignette 实验问卷系统</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh; 
            padding: 20px;
        }
        .container { max-width: 800px; margin: 0 auto; }
        
        header { 
            text-align: center; 
            color: white; 
            padding: 40px 0;
        }
        header h1 { font-size: 32px; margin-bottom: 10px; }
        header p { opacity: 0.9; font-size: 16px; }
        
        .survey-list { display: flex; flex-direction: column; gap: 16px; }
        .survey-card {
            background: white;
            border-radius: 16px;
            padding: 28px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }
        .survey-card:hover { transform: translateY(-4px); }
        
        .survey-title { 
            font-size: 20px; 
            font-weight: 600; 
            color: #333; 
            margin-bottom: 10px; 
        }
        .survey-desc { 
            color: #666; 
            font-size: 14px; 
            line-height: 1.6;
            margin-bottom: 20px; 
        }
        
        .stats {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
            padding: 16px;
            background: #f8f9fa;
            border-radius: 10px;
        }
        .stat { text-align: center; }
        .stat-value { font-size: 28px; font-weight: 700; color: #667eea; }
        .stat-label { font-size: 12px; color: #888; margin-top: 4px; }
        
        .groups {
            display: flex;
            gap: 12px;
            margin-bottom: 20px;
        }
        .group-badge {
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 500;
        }
        .group-low { background: #d4edda; color: #155724; }
        .group-high { background: #f8d7da; color: #721c24; }
        
        .btn-group { display: flex; gap: 10px; flex-wrap: wrap; }
        .btn {
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.2s;
        }
        .btn-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(102,126,234,0.4); }
        
        .btn-admin {
            position: fixed;
            top: 20px;
            right: 20px;
            background: rgba(255,255,255,0.2);
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            backdrop-filter: blur(10px);
        }
        .btn-admin:hover { background: rgba(255,255,255,0.3); }
        
        .empty-state {
            background: rgba(255,255,255,0.15);
            padding: 60px;
            border-radius: 20px;
            text-align: center;
            color: white;
        }
        .empty-state p { font-size: 18px; margin-bottom: 8px; }
        .empty-state small { opacity: 0.7; }
    </style>
</head>
<body>
    <a href="?action=admin" class="btn-admin">⚙️ 管理</a>
    
    <div class="container">
        <header>
            <h1>🔬 Vignette 实验问卷</h1>
            <p>情境实验数据收集系统</p>
        </header>
        
        <div class="survey-list">
            <?php if (empty($surveys)): ?>
            <div class="empty-state">
                <p style="font-size: 48px; margin-bottom: 20px;">📋</p>
                <p>暂无实验问卷</p>
                <small>管理员创建后即可开始收集数据</small>
            </div>
            <?php else: ?>
            <?php foreach ($surveys as $survey): ?>
            <?php 
                $responses = get_responses($survey['id']);
                $low = 0; $high = 0;
                foreach ($responses as $r) {
                    if (($r['group'] ?? '') === 'low') $low++;
                    elseif (($r['group'] ?? '') === 'high') $high++;
                }
            ?>
            <div class="survey-card">
                <div class="survey-title"><?php echo htmlspecialchars($survey['title']); ?></div>
                <div class="survey-desc"><?php echo htmlspecialchars($survey['description'] ?? ''); ?></div>
                
                <div class="groups">
                    <span class="group-badge group-low">低自主组: <?php echo $low; ?> 份</span>
                    <span class="group-badge group-high">高自主组: <?php echo $high; ?> 份</span>
                </div>
                
                <div class="stats">
                    <div class="stat">
                        <div class="stat-value"><?php echo count($responses); ?></div>
                        <div class="stat-label">总收集</div>
                    </div>
                </div>
                
                <div class="btn-group">
                    <a href="?action=survey&id=<?php echo urlencode($survey['id']); ?>" class="btn btn-primary">参与实验 →</a>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>