<?php
/**
 * 被试填写问卷 - 完整实验流程
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

$demographics = $survey['demographics'] ?? ['gender', 'age', 'education', 'ai_usage'];
?>
<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($survey['title']); ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 700px;
            margin: 0 auto;
        }
        
        .progress-bar {
            background: rgba(255,255,255,0.25);
            height: 8px;
            border-radius: 4px;
            margin-bottom: 24px;
            overflow: hidden;
        }
        .progress-bar .fill {
            height: 100%;
            background: white;
            border-radius: 4px;
            transition: width 0.4s ease;
            width: 0%;
        }
        
        .card {
            background: white;
            border-radius: 20px;
            padding: 36px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.15);
        }
        
        .page { display: none; }
        .page.active { display: block; }
        
        .page-title {
            font-size: 22px;
            font-weight: 600;
            color: #333;
            margin-bottom: 20px;
        }
        .page-desc {
            color: #666;
            line-height: 1.7;
            margin-bottom: 24px;
        }
        
        .form-group { margin-bottom: 24px; }
        .form-group label {
            display: block;
            font-weight: 500;
            color: #333;
            margin-bottom: 10px;
        }
        .form-group input, .form-group select {
            width: 100%;
            padding: 14px;
            border: 2px solid #e8e8e8;
            border-radius: 10px;
            font-size: 16px;
            font-family: inherit;
            transition: border-color 0.2s;
        }
        .form-group input:focus, .form-group select:focus {
            outline: none;
            border-color: #667eea;
        }
        
        /* 情境展示 */
        .scenario-box {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 24px;
            border-left: 4px solid #667eea;
        }
        .scenario-common {
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px dashed #ddd;
            color: #555;
            line-height: 1.8;
            font-size: 15px;
        }
        .scenario-specific {
            color: #333;
            line-height: 1.8;
            font-size: 15px;
        }
        
        /* 量表题目 */
        .question-item {
            margin-bottom: 28px;
            padding-bottom: 28px;
            border-bottom: 1px solid #f0f0f0;
        }
        .question-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        .question-text {
            font-size: 15px;
            color: #333;
            line-height: 1.7;
            margin-bottom: 14px;
        }
        .question-number {
            display: inline-block;
            background: #667eea;
            color: white;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            text-align: center;
            line-height: 24px;
            font-size: 13px;
            font-weight: 600;
            margin-right: 8px;
        }
        
        .scale-options {
            display: flex;
            gap: 8px;
        }
        .scale-option {
            flex: 1;
            text-align: center;
        }
        .scale-option input { display: none; }
        .scale-option label {
            display: block;
            padding: 12px 4px;
            background: #f5f5f5;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            color: #666;
            transition: all 0.2s;
        }
        .scale-option input:checked + label {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            transform: scale(1.05);
        }
        .scale-option label:hover { background: #e8e8e8; }
        
        .scale-labels {
            display: flex;
            justify-content: space-between;
            margin-top: 8px;
            font-size: 12px;
            color: #999;
        }
        
        /* 单选题目（人口学） */
        .radio-group {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .radio-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 16px;
            background: #f8f9fa;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.2s;
        }
        .radio-item:hover { background: #f0f0f0; }
        .radio-item input { display: none; }
        .radio-item .radio-circle {
            width: 20px;
            height: 20px;
            border: 2px solid #ddd;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
        }
        .radio-item input:checked + .radio-circle {
            border-color: #667eea;
            background: #667eea;
        }
        .radio-item input:checked + .radio-circle::after {
            content: '';
            width: 8px;
            height: 8px;
            background: white;
            border-radius: 50%;
        }
        .radio-item input:checked ~ span:last-child {
            color: #667eea;
            font-weight: 500;
        }
        
        /* 按钮 */
        .btn {
            padding: 14px 28px;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 500;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-primary { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
            color: white; 
        }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(102,126,234,0.4); }
        .btn-secondary { background: #f0f0f0; color: #666; }
        .btn-secondary:hover { background: #e0e0e0; }
        .btn:disabled { opacity: 0.5; cursor: not-allowed; transform: none; }
        
        .nav-buttons {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            margin-top: 30px;
        }
        .nav-buttons .btn { flex: 1; }
        
        /* 完成页 */
        .complete { text-align: center; padding: 40px 0; }
        .complete-icon { font-size: 72px; margin-bottom: 24px; }
        .complete h2 { font-size: 26px; color: #333; margin-bottom: 16px; }
        .complete p { color: #666; line-height: 1.8; font-size: 15px; }
        
        /* 响应式 */
        @media (max-width: 600px) {
            .card { padding: 24px; }
            .scale-options { flex-wrap: wrap; }
            .scale-option { flex: 0 0 18%; }
            .nav-buttons { flex-direction: column; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="progress-bar">
            <div class="fill" id="progressFill"></div>
        </div>
        
        <div class="card">
            <!-- P1: 知情同意 -->
            <div class="page" id="page-welcome">
                <h1 class="page-title">欢迎参与本研究</h1>
                <p class="page-desc">您好！感谢参与本次调查。本研究用于学术目的，匿名进行，不会记录您的个人身份信息，结果仅用于整体统计分析。问卷大约需要 6–8 分钟。</p>
                <p class="page-desc" style="background: #f8f9fa; padding: 16px; border-radius: 10px; border-left: 4px solid #667eea;"><strong>本问卷没有正确答案，请您根据自己的真实感受和直觉作答即可。</strong> 您可随时退出。</p>
                <p class="page-desc" style="color: #888; font-size: 14px;">点击下方"同意并开始"即表示您同意参与本研究。</p>
                <button class="btn btn-primary" style="width: 100%; margin-top: 20px;" onclick="nextPage()">同意并开始 →</button>
            </div>
            
            <!-- P2: 情境材料 -->
            <div class="page" id="page-scenario">
                <h1 class="page-title">情境阅读</h1>
                <p class="page-desc">请仔细阅读以下情境，并尽量代入这位员工的处境作答。</p>
                <div class="scenario-box">
                    <div class="scenario-common" id="scenarioCommon"><?php echo htmlspecialchars($survey['common_intro'] ?? ''); ?></div>
                    <div class="scenario-specific" id="scenarioSpecific"></div>
                </div>
                <p style="color: #888; font-size: 13px; text-align: center;">阅读完毕后，请点击"继续"开始作答。</p>
                <div class="nav-buttons">
                    <button class="btn btn-primary" style="flex: 1;" onclick="nextPage()">继续 →</button>
                </div>
            </div>
            
            <!-- P3: 操纵检验 (4题) -->
            <div class="page" id="page-manipulation">
                <h1 class="page-title">操纵检验题</h1>
                <p class="page-desc">请根据刚才阅读的情境，对以下说法进行评价。</p>
                <div id="manipulation-questions"></div>
                <div class="nav-buttons">
                    <button class="btn btn-secondary" onclick="prevPage()">← 上一题</button>
                    <button class="btn btn-primary" id="manipulation-next" onclick="nextPage()" disabled>下一题 →</button>
                </div>
            </div>
            
            <!-- P4: 预期压力 (4题) -->
            <div class="page" id="page-stress">
                <h1 class="page-title">预期压力</h1>
                <p class="page-desc">请想象如果您长期在这样的环境中工作，您的感受会如何。</p>
                <div id="stress-questions"></div>
                <div class="nav-buttons">
                    <button class="btn btn-secondary" onclick="prevPage()">← 上一题</button>
                    <button class="btn btn-primary" id="stress-next" onclick="nextPage()" disabled>下一题 →</button>
                </div>
            </div>
            
            <!-- P5: 技术压力 (4题) -->
            <div class="page" id="page-tech">
                <h1 class="page-title">技术压力</h1>
                <div id="tech-questions"></div>
                <div class="nav-buttons">
                    <button class="btn btn-secondary" onclick="prevPage()">← 上一题</button>
                    <button class="btn btn-primary" id="tech-next" onclick="nextPage()" disabled>下一题 →</button>
                </div>
            </div>
            
            <!-- P6: 控制感丧失 (3题) -->
            <div class="page" id="page-control">
                <h1 class="page-title">控制感</h1>
                <div id="control-questions"></div>
                <div class="nav-buttons">
                    <button class="btn btn-secondary" onclick="prevPage()">← 上一题</button>
                    <button class="btn btn-primary" id="control-next" onclick="nextPage()" disabled>下一题 →</button>
                </div>
            </div>
            
            <!-- P7: 真实性 + 注意力检验 -->
            <div class="page" id="page-realism">
                <h1 class="page-title">情境评价</h1>
                <div id="realism-questions"></div>
                <div class="nav-buttons">
                    <button class="btn btn-secondary" onclick="prevPage()">← 上一题</button>
                    <button class="btn btn-primary" id="realism-next" onclick="nextPage()" disabled>下一题 →</button>
                </div>
            </div>
            
            <!-- P8: 人口学 -->
            <div class="page" id="page-demographics">
                <h1 class="page-title">基本信息</h1>
                <p class="page-desc">以下问题用于统计分析，请根据您的实际情况选择。</p>
                <div id="demo-questions"></div>
                <div class="nav-buttons">
                    <button class="btn btn-secondary" onclick="prevPage()">← 上一题</button>
                    <button class="btn btn-primary" id="demo-next" onclick="nextPage()" disabled>提交 →</button>
                </div>
            </div>
            
            <!-- P9: 完成 -->
            <div class="page" id="page-complete">
                <div class="complete">
                    <div class="complete-icon">✅</div>
                    <h2>感谢您的参与！</h2>
                    <p>本次调查到此结束，非常感谢！<br>您刚才阅读的是一个假设情境，用于了解人们的一般看法。您的回答对学术研究非常有价值。</p>
                    <p style="margin-top: 20px; color: #888; font-size: 13px;">您的答题时长: <span id="totalTime">0</span> 秒</p>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        const surveyId = '<?php echo $survey_id; ?>';
        const commonIntro = <?php echo json_encode($survey['common_intro'] ?? ''); ?>;
        const scenarioLow = <?php echo json_encode($survey['scenario_low'] ?? ''); ?>;
        const scenarioHigh = <?php echo json_encode($survey['scenario_high'] ?? ''); ?>;
        const demographicsConfig = <?php echo json_encode($demographics); ?>;
        
        // 页面配置
        const pages = [
            { id: 'page-welcome', progress: 0 },
            { id: 'page-scenario', progress: 11 },
            { id: 'page-manipulation', progress: 22 },
            { id: 'page-stress', progress: 44 },
            { id: 'page-tech', progress: 55 },
            { id: 'page-control', progress: 67 },
            { id: 'page-realism', progress: 78 },
            { id: 'page-demographics', progress: 89 },
            { id: 'page-complete', progress: 100 }
        ];
        
        let currentPage = 0;
        let group = Math.random() < 0.5 ? 'low' : 'high'; // 随机分组
        let startTime = Date.now();
        let pageStartTime = Date.now();
        
        // 答案存储
        let answers = {
            manipulation_check: [],
            anticipated_stress: [],
            tech_anxiety: [],
            loss_of_control: [],
            realism: null,
            attention_check: null,
            demographics: {}
        };
        
        // 题目数据
        const questions = {
            manipulation_check: [
                '情境中的 AI 系统能够自主规划任务流程。',
                '情境中的 AI 系统能自主做出判断，无需该员工频繁干预。',
                '情境中的 AI 系统在执行任务时拥有较高的决策权。',
                '在情境描述的工作流程中，主导权在 AI 一方而非员工。'
            ],
            anticipated_stress: [
                '如果长期在这样的环境中工作，我会感到精疲力竭。',
                '如果长期在这样的环境中工作，一想到上班我就会感到疲惫。',
                '如果长期在这样的环境中工作，我会对工作的意义产生怀疑。',
                '如果长期在这样的环境中工作，我会对工作变得冷漠。'
            ],
            tech_anxiety: [
                '在这样的环境中，我需要花很多精力去理解和验证 AI 的输出。',
                '在这样的环境中，跟上 AI 的运作方式会让我有压力。',
                '在这样的环境中，使用这套 AI 系统会让我感到紧张或焦虑。',
                '在这样的环境中，我会担心自己的技能变得过时。'
            ],
            loss_of_control: [
                '在这样的环境中，我对自己的工作几乎没有掌控感。',
                '在这样的环境中，重要决定不再由我做主。',
                '在这样的环境中，我只是在执行别人（AI）的安排。'
            ],
            realism: ['刚才描述的工作情境真实可信。'],
            attention: ['这道题请直接选择"基本同意"。']
        };
        
        // 生成量表题目HTML
        function createScaleQuestions(questionList, prefix, nextBtnId) {
            return questionList.map((q, i) => `
                <div class="question-item">
                    <div class="question-text">
                        <span class="question-number">${i + 1}</span>
                        ${q}
                    </div>
                    <div class="scale-options">
                        ${[1,2,3,4,5].map(v => `
                            <div class="scale-option">
                                <input type="radio" name="${prefix}_${i}" value="${v}" id="${prefix}_${i}_${v}">
                                <label for="${prefix}_${i}_${v}">${v}</label>
                            </div>
                        `).join('')}
                    </div>
                    <div class="scale-labels">
                        <span>完全不同意</span>
                        <span>完全同意</span>
                    </div>
                </div>
            `).join('');
        }
        
        // 初始化页面
        function init() {
            // 设置情境
            document.getElementById('scenarioSpecific').textContent = 
                group === 'low' ? scenarioLow : scenarioHigh;
            
            // 操纵检验
            document.getElementById('manipulation-questions').innerHTML = 
                createScaleQuestions(questions.manipulation_check, 'mc', 'manipulation-next');
            setupScaleListener('mc', 4, 'manipulation-next');
            
            // 预期压力
            document.getElementById('stress-questions').innerHTML = 
                createScaleQuestions(questions.anticipated_stress, 'stress', 'stress-next');
            setupScaleListener('stress', 4, 'stress-next');
            
            // 技术压力
            document.getElementById('tech-questions').innerHTML = 
                createScaleQuestions(questions.tech_anxiety, 'tech', 'tech-next');
            setupScaleListener('tech', 4, 'tech-next');
            
            // 控制感
            document.getElementById('control-questions').innerHTML = 
                createScaleQuestions(questions.loss_of_control, 'control', 'control-next');
            setupScaleListener('control', 3, 'control-next');
            
            // 真实性 + 注意力
            document.getElementById('realism-questions').innerHTML = 
                createScaleQuestions(questions.realism, 'realism', 'realism-next') +
                createScaleQuestions(questions.attention, 'attention', 'realism-next');
            setupScaleListener('realism', 1, 'realism-next');
            setupScaleListener('attention', 1, 'realism-next');
            
            // 人口学
            initDemographics();
            
            // 显示首页
            showPage(0);
        }
        
        // 人口学字段配置
        function initDemographics() {
            const container = document.getElementById('demo-questions');
            let html = '';
            
            if (demographicsConfig.includes('gender')) {
                html += `
                    <div class="form-group">
                        <label>性别</label>
                        <div class="radio-group">
                            <label class="radio-item"><input type="radio" name="gender" value="男"><span class="radio-circle"></span><span>男</span></label>
                            <label class="radio-item"><input type="radio" name="gender" value="女"><span class="radio-circle"></span><span>女</span></label>
                        </div>
                    </div>
                `;
            }
            
            if (demographicsConfig.includes('age')) {
                html += `
                    <div class="form-group">
                        <label>年龄</label>
                        <div class="radio-group">
                            <label class="radio-item"><input type="radio" name="age" value="25及以下"><span class="radio-circle"></span><span>25岁及以下</span></label>
                            <label class="radio-item"><input type="radio" name="age" value="26-35"><span class="radio-circle"></span><span>26-35岁</span></label>
                            <label class="radio-item"><input type="radio" name="age" value="36-45"><span class="radio-circle"></span><span>36-45岁</span></label>
                            <label class="radio-item"><input type="radio" name="age" value="46及以上"><span class="radio-circle"></span><span>46岁及以上</span></label>
                        </div>
                    </div>
                `;
            }
            
            if (demographicsConfig.includes('education')) {
                html += `
                    <div class="form-group">
                        <label>学历</label>
                        <div class="radio-group">
                            <label class="radio-item"><input type="radio" name="education" value="大专及以下"><span class="radio-circle"></span><span>大专及以下</span></label>
                            <label class="radio-item"><input type="radio" name="education" value="本科"><span class="radio-circle"></span><span>本科</span></label>
                            <label class="radio-item"><input type="radio" name="education" value="硕士及以上"><span class="radio-circle"></span><span>硕士及以上</span></label>
                        </div>
                    </div>
                `;
            }
            
            if (demographicsConfig.includes('ai_usage')) {
                html += `
                    <div class="form-group">
                        <label>您在实际工作中使用过 AI 工具吗？</label>
                        <div class="radio-group">
                            <label class="radio-item"><input type="radio" name="ai_usage" value="没有"><span class="radio-circle"></span><span>没有</span></label>
                            <label class="radio-item"><input type="radio" name="ai_usage" value="偶尔"><span class="radio-circle"></span><span>偶尔</span></label>
                            <label class="radio-item"><input type="radio" name="ai_usage" value="经常"><span class="radio-circle"></span><span>经常</span></label>
                        </div>
                    </div>
                `;
            }
            
            container.innerHTML = html;
            
            // 监听人口学选择
            container.querySelectorAll('input[type="radio"]').forEach(input => {
                input.addEventListener('change', () => {
                    checkDemographics();
                });
            });
        }
        
        // 检查人口学是否完成
        function checkDemographics() {
            const required = demographicsConfig;
            let allFilled = true;
            
            required.forEach(field => {
                const input = document.querySelector(`input[name="${field}"]:checked`);
                if (!input) allFilled = false;
            });
            
            document.getElementById('demo-next').disabled = !allFilled;
        }
        
        // 量表监听
        function setupScaleListener(prefix, count, nextBtnId) {
            const container = document.querySelector(`#${nextBtnId.replace('-next', '-questions')}`);
            
            // 监听所有radio变化
            for (let i = 0; i < count; i++) {
                const inputs = document.querySelectorAll(`input[name="${prefix}_${i}"]`);
                inputs.forEach(input => {
                    input.addEventListener('change', () => {
                        checkScaleComplete(prefix, count, nextBtnId);
                    });
                });
            }
        }
        
        function checkScaleComplete(prefix, count, nextBtnId) {
            let allAnswered = true;
            for (let i = 0; i < count; i++) {
                const checked = document.querySelector(`input[name="${prefix}_${i}"]:checked`);
                if (!checked) allAnswered = false;
            }
            document.getElementById(nextBtnId).disabled = !allAnswered;
        }
        
        // 收集答案
        function collectAnswers() {
            // 操纵检验
            for (let i = 0; i < 4; i++) {
                const checked = document.querySelector(`input[name="mc_${i}"]:checked`);
                answers.manipulation_check.push(checked ? parseInt(checked.value) : null);
            }
            
            // 预期压力
            for (let i = 0; i < 4; i++) {
                const checked = document.querySelector(`input[name="stress_${i}"]:checked`);
                answers.anticipated_stress.push(checked ? parseInt(checked.value) : null);
            }
            
            // 技术压力
            for (let i = 0; i < 4; i++) {
                const checked = document.querySelector(`input[name="tech_${i}"]:checked`);
                answers.tech_anxiety.push(checked ? parseInt(checked.value) : null);
            }
            
            // 控制感
            for (let i = 0; i < 3; i++) {
                const checked = document.querySelector(`input[name="control_${i}"]:checked`);
                answers.loss_of_control.push(checked ? parseInt(checked.value) : null);
            }
            
            // 真实性
            const realismChecked = document.querySelector('input[name="realism_0"]:checked');
            answers.realism = realismChecked ? parseInt(realismChecked.value) : null;
            
            // 注意力
            const attentionChecked = document.querySelector('input[name="attention_0"]:checked');
            answers.attention_check = attentionChecked ? parseInt(attentionChecked.value) : null;
            
            // 人口学
            demographicsConfig.forEach(field => {
                const input = document.querySelector(`input[name="${field}"]:checked`);
                if (input) answers.demographics[field] = input.value;
            });
        }
        
        // 显示页面
        function showPage(index) {
            document.querySelectorAll('.page').forEach(p => p.classList.remove('active'));
            document.getElementById(pages[index].id).classList.add('active');
            
            // 更新进度
            document.getElementById('progressFill').style.width = pages[index].progress + '%';
            
            currentPage = index;
            pageStartTime = Date.now();
        }
        
        // 上一页
        function prevPage() {
            if (currentPage > 0) {
                showPage(currentPage - 1);
            }
        }
        
        // 下一页
        function nextPage() {
            if (currentPage < pages.length - 1) {
                showPage(currentPage + 1);
                
                // 最后一页提交
                if (currentPage === pages.length - 2) {
                    collectAnswers();
                    submitSurvey();
                }
            }
        }
        
        // 提交
        async function submitSurvey() {
            const totalTime = Math.round((Date.now() - startTime) / 1000);
            document.getElementById('totalTime').textContent = totalTime;
            
            const responseData = {
                participant_id: 'P' + Math.random().toString(36).substr(2, 6).toUpperCase(),
                group: group,
                started_at: startTime,
                submitted_at: Date.now(),
                total_time: totalTime,
                manipulation_check: answers.manipulation_check,
                anticipated_stress: answers.anticipated_stress,
                tech_anxiety: answers.tech_anxiety,
                loss_of_control: answers.loss_of_control,
                realism: answers.realism,
                attention_check: answers.attention_check,
                demographics: answers.demographics
            };
            
            try {
                await fetch('?action=api&method=submitResponse', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        survey_id: surveyId,
                        data: responseData
                    })
                });
            } catch (err) {
                console.error('提交失败:', err);
            }
        }
        
        // 启动
        init();
    </script>
</body>
</html>