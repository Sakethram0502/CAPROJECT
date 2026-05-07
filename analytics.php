<?php
session_start();
include('db.php');

$programme  = strtolower($_GET['programme'] ?? 'mca');
if (!in_array($programme, ['mca','bca'])) $programme = 'mca';
$prog_label = strtoupper($programme);

// ═══════════════════════════════════════════════════════
// DOMAIN CLASSIFIER
// ═══════════════════════════════════════════════════════
function classifyDomain(string $title): string {
    $t = strtolower($title);
    $domains = [
        'Deep Learning'              => ['deep learning','cnn','convolutional','lstm','rnn','gnn','transformer','gan','vgg','resnet','efficientnet','mobilenet','bert','yolo','u-net','densenet','alexnet','neural network','deberta'],
        'Machine Learning'           => ['machine learning','svm','random forest','xgboost','regression','classification','clustering','naive bayes','decision tree','ensemble','gradient boost','feature selection','ant colony','semi-supervised','passive aggressive'],
        'Healthcare & Medical AI'    => ['cancer','tumor','tumour','diabetic','disease','healthcare','medical','alzheimer','cardiac','cardiovascular','retinopathy','lung','brain','breast','skin','parkinson','thyroid','liver','covid','gastrointestinal','ehr','drug','health'],
        'Computer Vision'            => ['image','detection','recognition','object','face','facial','pedestrian','segmentation','super resolution','colorization','pose estimation','tracking','license plate','mammogram','satellite','seismic','underwater','captioning','logo','salt','depth'],
        'NLP & Text'                 => ['sentiment','nlp','text','language','fake news','review','bert','natural language','document','opinion','summarization','translation','linguistic','monkeypox tweets'],
        'Cybersecurity & Fraud'      => ['fraud','intrusion','anomaly','deepfake','cryptography','security','malware','authentication','threat','biometric','vein','fingerprint'],
        'Data Science & Forecasting' => ['forecasting','prediction','analysis','analytics','recommendation','time series','supply chain','demand','visualization','graph centrality','drowsiness','demographic'],
        'Speech & Audio'             => ['speech','voice','audio','music','sound','emotion recognition from speech','voice conversion','voice authentication'],
        'Full Stack & Systems'       => ['web','application','mobile app','system','platform','framework','dashboard','management','portal','api','full stack','traffic management'],
    ];
    foreach ($domains as $label => $keywords) {
        foreach ($keywords as $kw) {
            if (str_contains($t, $kw)) return $label;
        }
    }
    return 'Other / Interdisciplinary';
}

/* ── MCA Hardcoded Batch Data ── */
$MCA_BATCH_DATA = [
    2023 => [
        ['reg_no'=>'231FD01001','student_name'=>'A. Sravanthi','project_title'=>'Novel Brain Tumor Detection Using Deep Learning Techniques','guide_name'=>'Dr. Santhi Sri Kurra'],
        ['reg_no'=>'231FD01002','student_name'=>'A. S. L. Karthik Chaitanya Kumar','project_title'=>'A Social Media Sentiment Analysis Exploration Using Deep Learning','guide_name'=>'Dr. R. S. Padma Priya'],
        ['reg_no'=>'231FD01004','student_name'=>'A. Bhargav','project_title'=>'Hybrid CMS-GAN Model for Enhanced Pedestrian Detection Through Unpaired Night-to-Day Translation','guide_name'=>'Dr. Guruswamy Shivakumar'],
        ['reg_no'=>'231FD01005','student_name'=>'A. Harshitha Yamini','project_title'=>'Emotion Recognition from Speech Using Hybrid Deep Learning Techniques','guide_name'=>'Dr. K. Santhi Sri'],
        ['reg_no'=>'231FD01007','student_name'=>'B. Yaswini Sai','project_title'=>'Image Captioning Using ResNet-50 and LSTM','guide_name'=>'Dr. Santhi Sri Kurra'],
        ['reg_no'=>'231FD01009','student_name'=>'B. Nandini','project_title'=>'Food Demand Supply Chain Modelling and Time Series Forecasting Based on ML and DL','guide_name'=>'Dr. Prashanti Guttikonda'],
        ['reg_no'=>'231FD01010','student_name'=>'Ch. Hari Krishna','project_title'=>'Deep Neural Network Model-Based Ship Images Classification','guide_name'=>'Dr. Siva Koteswara Rao Chinnam'],
        ['reg_no'=>'231FD01012','student_name'=>'Ch. Krishnaveni','project_title'=>'Healthcare Disease Analysis Using Deep Learning Classification Model','guide_name'=>'Dr. Siva Koteswara Rao Chinnam'],
        ['reg_no'=>'231FD01016','student_name'=>'D. Sai Sasikanth','project_title'=>'Ad Click Fraud Detection Using Machine Learning and Deep Learning Techniques','guide_name'=>'Dr. Prashanti Guttikonda'],
        ['reg_no'=>'231FD01017','student_name'=>'G. Madhuri','project_title'=>'Lung Cancer Detection Using Hyperparameter Techniques with Machine Learning Models','guide_name'=>'Dr. Siva Koteswara Rao Chinnam'],
        ['reg_no'=>'231FD01018','student_name'=>'G. N. E. Mounika','project_title'=>'Brain Tumor Classification and Detection Using Deep Learning','guide_name'=>'Dr. Guruswamy Shivakumar'],
        ['reg_no'=>'231FD01022','student_name'=>'K. Mounika Sai Sri','project_title'=>'Wind Power Forecasting By Using Machine Learning and Deep Learning','guide_name'=>'Dr. K. Santhi Sri'],
        ['reg_no'=>'231FD01024','student_name'=>'K. Sitamma','project_title'=>'Dash Sylvereye: A High-Performance Visualization System for City-Scale Street Networks','guide_name'=>'Dr. K. Santhi Sri'],
        ['reg_no'=>'231FD01025','student_name'=>'K. Divya Swetha','project_title'=>'Adaptive Object Detection with ESRGAN-Enhanced Resolution and Faster R-CNN','guide_name'=>'Dr. Prashanti Guttikonda'],
        ['reg_no'=>'231FD01028','student_name'=>'K. Srinivasasrao','project_title'=>'Automated Diabetic Retinopathy Grading Using Graph Neural Networks for Enhanced Feature Relationship Analysis in Retinal Images','guide_name'=>'Dr. Gayatri Ketepalli'],
        ['reg_no'=>'231FD01030','student_name'=>'K. B. S. Priyanka','project_title'=>'Enhancing Emotional Voice Conversion with Intensity Control and Mixed Embedding','guide_name'=>'Mr. Siva Rao Alakunta'],
        ['reg_no'=>'231FD01031','student_name'=>'K. Durga Bhavani','project_title'=>'Enhanced ADHD Detection in Children with Autism Spectrum Disorder Using Advanced Handwriting Analysis and Machine Learning','guide_name'=>'Dr. Guruswamy Shivakumar'],
        ['reg_no'=>'231FD01032','student_name'=>'K. Pavan Kumar','project_title'=>'Enhanced Image Translation with Pixel Shuffler and Self-Attention GANs','guide_name'=>'Dr. Guruswamy Shivakumar'],
        ['reg_no'=>'231FD01035','student_name'=>'K. Bala Gopaludu','project_title'=>'Deep Learning-Driven Multi Genre Music Classification With Ten Sound Types','guide_name'=>'Dr. R. S. Padma Priya'],
        ['reg_no'=>'231FD01036','student_name'=>'M. Rama Krishna Koushik','project_title'=>'Enhancing Text Classification in EHRs with Adaptive Context-Aware Heterogeneous Graph Attention Networks','guide_name'=>'Dr. Gayatri Ketepalli'],
        ['reg_no'=>'231FD01038','student_name'=>'M. Srimannarayana','project_title'=>'Evaluating Machine Learning Approaches for Effective Fake News Detection','guide_name'=>'Dr. Gayatri Ketepalli'],
        ['reg_no'=>'231FD01041','student_name'=>'N. Eswar','project_title'=>'Deep Learning-Based Facial Emotion Recognition: A CNN Approach for Real-Time Applications','guide_name'=>'Dr. Gayatri Ketepalli'],
        ['reg_no'=>'231FD01042','student_name'=>'P. Gopi Krishna','project_title'=>'A Framework of Segmentation and Classification Models for Breast Tumor from Mammogram Images','guide_name'=>'Mrs. Sk. Nazma Sultana'],
        ['reg_no'=>'231FD01043','student_name'=>'P. Tirumala','project_title'=>'Voice Authentication at Scale: A Room Level Approach for Smart Devices','guide_name'=>'Mr. D. Anandhakumar'],
        ['reg_no'=>'231FD01044','student_name'=>'P. Azemunnisa','project_title'=>'Credit Card Fraud Detection Through Time-Aware Behavioral Representation Learning','guide_name'=>'Dr. R. S. Padma Priya'],
        ['reg_no'=>'231FD01045','student_name'=>'P. Srinivasa Reddy','project_title'=>'Diabetes Prediction Using Machine Learning Techniques','guide_name'=>'Dr. Prashanti Guttikonda'],
        ['reg_no'=>'231FD01046','student_name'=>'P. Teja Sai','project_title'=>'Gemstone Classification Using Gemtelligence Framework: A Deep Learning Approach','guide_name'=>'Dr. Siva Koteswara Rao Chinnam'],
        ['reg_no'=>'231FD01047','student_name'=>'P. Durga Prasad','project_title'=>'Enhanced Deep Learning for Diabetic Retinopathy Detection with Optimized Feature Extraction','guide_name'=>'Dr. K. Gayatri'],
        ['reg_no'=>'231FD01049','student_name'=>'Prakash Sharma','project_title'=>'Deep Learning for Wide Dynamic Range Resolution Reconstruction of Images','guide_name'=>'Dr. Guruswamy Shivakumar'],
        ['reg_no'=>'231FD01051','student_name'=>'R. Vardhan','project_title'=>'Gastrointestinal Disease Detection Using RegNet, MobileNetV2, EfficientNetB2 and ResNet-152 with Transformers','guide_name'=>'Dr. R. S. Padma Priya'],
        ['reg_no'=>'231FD01052','student_name'=>'R. Mahesh Babu','project_title'=>'Human Pose Estimation Using AI Tool','guide_name'=>'Dr. K. Santhi Sri'],
        ['reg_no'=>'231FD01053','student_name'=>'S. Sowjanya','project_title'=>'Graph Neural Networks for Financial Fraud Detection in Online Transactions','guide_name'=>'Dr. Siva Koteswara Rao Chinnam'],
        ['reg_no'=>'231FD01054','student_name'=>'S. Vasavi Venkata Lakshmi','project_title'=>'Secure Data Access in Cloud Environments Using Quantum Cryptography','guide_name'=>'Dr. Prashanti Guttikonda'],
        ['reg_no'=>'231FD01056','student_name'=>'Shaik Arifunnisa','project_title'=>'Pre-Trained Models for Breast Cancer Image Classification','guide_name'=>'Dr. K. Santhi Sri'],
        ['reg_no'=>'231FD01058','student_name'=>'Shaik Hafeeza','project_title'=>'EHR-HGCN: A Transformer Augmented Heterogeneous Graph Convolutional Network for Enhanced Text Classification in Healthcare','guide_name'=>'Dr. K. Gayatri'],
        ['reg_no'=>'231FD01059','student_name'=>'Shaik Mahera','project_title'=>'Enhancing Breast Cancer Diagnosis Using Modality-Specific Information Disentanglement from MRI','guide_name'=>'Dr. Siva Koteswara Rao Chinnam'],
        ['reg_no'=>'231FD01061','student_name'=>'Soni Kumari','project_title'=>'A Deep Learning Approach for Underwater Image Enhancement Using CNNs','guide_name'=>'Mrs. R. Naga Sirisha'],
        ['reg_no'=>'231FD01063','student_name'=>'T. Nived Reddy','project_title'=>'An Enhanced Fake News Detection System with Deep Learning Methods','guide_name'=>'Dr. R. S. Padma Priya'],
        ['reg_no'=>'231FD01064','student_name'=>'T. Chandrashekar','project_title'=>'Face Detection in Low-Light Conditions Using MTCNN and YOLOv8','guide_name'=>'Mrs. R. Naga Sirisha'],
        ['reg_no'=>'231FD01065','student_name'=>'T. Srihari','project_title'=>'Instance-Level Human Parts Detection and a New Benchmark','guide_name'=>'Dr. Prashanti Guttikonda'],
        ['reg_no'=>'231FD01067','student_name'=>'T. Syam Tejaswi','project_title'=>'Context-Aware Chronic Disease Prediction Using Machine Learning','guide_name'=>'Dr. R. S. Padma Priya'],
        ['reg_no'=>'231FD01068','student_name'=>'Tagore Thotakura','project_title'=>'Lung Cancer Detection Using Machine Learning','guide_name'=>'Mrs. R. Naga Sirisha'],
        ['reg_no'=>'231FD01069','student_name'=>'T. Kamal','project_title'=>'Disease Prediction and Drug Recommendation Using Machine Learning','guide_name'=>'Dr. Siva Koteswara Rao Chinnam'],
        ['reg_no'=>'231FD01071','student_name'=>'V. Anil','project_title'=>'Adaptive Context-Aware Framework for Mammography Projection Reconstruction Using Dynamic Deep Learning Architectures','guide_name'=>'Dr. Guruswamy Shivakumar'],
        ['reg_no'=>'231FD01072','student_name'=>'Y. Venkata Arjun Reddy','project_title'=>'An Optimized Machine Learning Model for Heart Disease Prediction','guide_name'=>'Dr. Siva Koteswara Rao Chinnam'],
        ['reg_no'=>'231FD01074','student_name'=>'L. Venkata Sriram','project_title'=>'Advanced License Plate Recognition System for Smart Traffic Management and Monitoring','guide_name'=>'Dr. Gayatri Ketepalli'],
        ['reg_no'=>'231FD01075','student_name'=>'A. Chenchi Reddy','project_title'=>'Dual Stream Emotion Recognition Using Facial Expressions Using Deep Learning','guide_name'=>'Dr. Guruswamy Shivakumar'],
    ],
    2022 => [
        ['reg_no'=>'221FD01002','student_name'=>'A. Amareshwara Sai Nath','project_title'=>'Interactive Deep Image Colorization of Quality','guide_name'=>'Dr. Ziaul Haque Choudhury'],
        ['reg_no'=>'221FD01003','student_name'=>'A. Gnaneswari','project_title'=>'Semantic Segmentation of Brain Tumour Detection Using MRI Images','guide_name'=>'Dr. G. Shiva Kumar'],
        ['reg_no'=>'221FD01009','student_name'=>'B. Naga Prathima','project_title'=>'Instance-Level Human Parts Detection and a New Benchmark','guide_name'=>'Mrs. K. Gayatri'],
        ['reg_no'=>'221FD01012','student_name'=>'B. Gopinadh','project_title'=>'A Mobile Application for Real Time Object Detection Using Deep Learning','guide_name'=>'Dr. Hemanta Kumar Bhuyan'],
        ['reg_no'=>'221FD01013','student_name'=>'Boyapati Charani','project_title'=>'Customized CNN Models for Diabetic Retinopathy Detection: A Comparative Study of Architectural Approaches','guide_name'=>'Dr. Kamepalli Sujatha'],
        ['reg_no'=>'221FD01014','student_name'=>'Ch. Jahnavi','project_title'=>'Masked Face Recognition: Advancing Accuracy with CNN, Mask Transfer, and Attention Model','guide_name'=>'Dr. Ziaul Haque Choudhury'],
        ['reg_no'=>'221FD01016','student_name'=>'D. Aravind Kumar','project_title'=>'Identification of Deepfake Images Using Deep Learning','guide_name'=>'Mrs. K. Gayatri'],
        ['reg_no'=>'221FD01018','student_name'=>'Devabattini Naga Divya','project_title'=>'Exploring the Role of Feature Selection in Developing Robust Cardiovascular Disease Prediction Models','guide_name'=>'Dr. K. Sujatha'],
        ['reg_no'=>'221FD01019','student_name'=>'Devandla Vamsi','project_title'=>'Real-Time Facial Emotions Recognition Using Deep Learning Approach','guide_name'=>'Dr. P. Subba Rao'],
        ['reg_no'=>'221FD01020','student_name'=>'Dosapati Lakshmi Naga Durga Parvathi','project_title'=>'A Multi-Model Deep Learning Approach for Lung Cancer Detection','guide_name'=>'Dr. N. Veeranjaneyulu'],
        ['reg_no'=>'221FD01022','student_name'=>'E. Durga Prasad','project_title'=>'Using Machine Learning for Network Anomaly and Intrusion Detection','guide_name'=>'Mrs. K. Gayatri'],
        ['reg_no'=>'221FD01023','student_name'=>'E. Vasubabu','project_title'=>'A Multichannel Approach with Graph Centrality Relation to Detect Drowsiness of a Driver','guide_name'=>'K. Praveen Kumar'],
        ['reg_no'=>'221FD01024','student_name'=>'G. Lakshmi Sai Chandrika','project_title'=>'A Deep Learning Approach to Classifying and Detecting Tomato Leaf Disease','guide_name'=>'Dr. G. Shiva Kumar'],
        ['reg_no'=>'221FD01025','student_name'=>'Gonuguntla Srilatha','project_title'=>'Crop Recommendation Using Ant Colony Optimization and Semi-Supervised SVM','guide_name'=>'Dr. Siva Koteswararao Chinnam'],
        ['reg_no'=>'221FD01026','student_name'=>'G. N. Koteswararao','project_title'=>'Deep Learning-Based Semantic Segmentation for Salt Deposit Detection','guide_name'=>'Mrs. K. Gayatri'],
        ['reg_no'=>'221FD01028','student_name'=>'J. Vidyadhari','project_title'=>'Age Sense Demographic Insight System','guide_name'=>'Dr. K. Santhi Sri'],
        ['reg_no'=>'221FD01029','student_name'=>'J. Vamsi Krishna','project_title'=>'A Flower Classification Method Combining DenseNet Architecture with SVM','guide_name'=>'Dr. G. Shiva Kumar'],
        ['reg_no'=>'221FD01030','student_name'=>'K. Naga Anjali','project_title'=>'Cardiovascular Disease Detection Using MultiChannel Inception DenseNet','guide_name'=>'K. Praveen Kumar'],
        ['reg_no'=>'221FD01031','student_name'=>'K. Anusha','project_title'=>'Medical Image Segmentation Using Enhanced Attention Based Transformer Model','guide_name'=>'Dr. Hemanta Kumar Bhuyan'],
        ['reg_no'=>'221FD01033','student_name'=>'K. Raghava','project_title'=>'Real-Time Detection of Covid-19 Face Masks with TensorFlow, Keras and OpenCV','guide_name'=>'Dr. P. Subba Rao'],
        ['reg_no'=>'221FD01034','student_name'=>'Kanna Gopi Babu','project_title'=>'Leveraging Machine Learning to Improve Early Detection of Thyroid Cancer','guide_name'=>'Dr. K. Sujatha'],
        ['reg_no'=>'221FD01035','student_name'=>'K. Gayathri','project_title'=>'Multi Agent Learning Technique for Feature Selection','guide_name'=>'Dr. Hemanta Kumar Bhuyan'],
        ['reg_no'=>'221FD01036','student_name'=>'K. Usha Rani','project_title'=>'Deep Learning for Emotion Recognition: Evaluating CNN on Facial Expressions','guide_name'=>'Dr. N. Veeranjaneyulu'],
        ['reg_no'=>'221FD01037','student_name'=>'K. Ramya Sri','project_title'=>'Zero-Shot Image-to-Image Translation','guide_name'=>'Sk. Nymathulla'],
        ['reg_no'=>'221FD01038','student_name'=>'K. Lakshmi Thanuja','project_title'=>'Heart Disease Prediction System Using Ensemble Methods','guide_name'=>'Dr. K. Santhi Sri'],
        ['reg_no'=>'221FD01039','student_name'=>'Hima Bindu Kurapati','project_title'=>'Fraud Detection in Online Payments Using Machine Learning','guide_name'=>'Dr. K. Santhi Sri'],
        ['reg_no'=>'221FD01040','student_name'=>'K. Rajya Lakshmi','project_title'=>'Diagnosis of Brain Tumor Using Machine Learning Model with Fine Hyper Parameter Tuning Approach','guide_name'=>'V. Nagireddy'],
        ['reg_no'=>'221FD01041','student_name'=>'M. Sumanvitha','project_title'=>'Deep Learning for Face Recognition Using Multi-Task Cascaded CNN and AlexNet Approaches','guide_name'=>'Dr. P. Subbarao'],
        ['reg_no'=>'221FD01042','student_name'=>'Maram Venkatalakshmi','project_title'=>'Deep Learning Model for Logo Detection','guide_name'=>'Dr. Siva Koteswararao Chinnam'],
        ['reg_no'=>'221FD01043','student_name'=>'M. Venkata Raju','project_title'=>'Detection and Recognition of Human Face Emotion Using Neural Network','guide_name'=>'Dr. Ziaul Haque Choudhury'],
        ['reg_no'=>'221FD01044','student_name'=>'M. Sowmya','project_title'=>'Signet: CNN-Based Technology for Inclusive Communication with Deaf and Mute Individuals','guide_name'=>'Dr. Shiva Kumar G'],
        ['reg_no'=>'221FD01045','student_name'=>'N. Venkata Siva Rao','project_title'=>'A Probabilistic Linguistic Term Based Product Review Sentiment Analysis Using BERT Representation','guide_name'=>'Mr. K. Praveen Kumar'],
        ['reg_no'=>'221FD01046','student_name'=>'N. Pavan Kalyan','project_title'=>'Masked Face-Recognition By Using ResNet-50, CV2, SVC Models','guide_name'=>'Dr. P. Subbarao'],
        ['reg_no'=>'221FD01047','student_name'=>'P. Mohan Reddy','project_title'=>'A Hybrid Stacked Conv Bidirectional LSTM Approach for Predicting Public Opinions from Monkeypox Tweets','guide_name'=>'V. Nagi Reddy'],
        ['reg_no'=>'221FD01048','student_name'=>'P. Alekhya','project_title'=>'Text and Non-text Segmentation in Printed Document Images Using CNN and VGG16','guide_name'=>'Dr. Shiva Kumar G'],
        ['reg_no'=>'221FD01049','student_name'=>'P. Rahul','project_title'=>'Face Recognition Using Hybrid Learning Model','guide_name'=>'Dr. Ziaul Haque Choudhury'],
        ['reg_no'=>'221FD01050','student_name'=>'P. Prasanna Lakshmi','project_title'=>"Alzheimer's Disease Detection Using VGG16",'guide_name'=>'Dr. K. Santhi Sri'],
        ['reg_no'=>'221FD01051','student_name'=>'P. Gowthami','project_title'=>'Integrated Threat Mitigation in Finger and Palm Vein Biometrics Through Multi-Algorithm Fusion','guide_name'=>'Dr. Ziaul Haque Choudhury'],
        ['reg_no'=>'221FD01052','student_name'=>'P. Ganesh','project_title'=>'Heart Disease Prediction Using VGG16','guide_name'=>'Dr. Ziaul Haque Choudhury'],
        ['reg_no'=>'221FD01054','student_name'=>'R. Tarun Gopal','project_title'=>'Adaptive Multilayer Perceptual Attention Network for Facial Expression Recognition','guide_name'=>'Dr. Hemanta Kumar Bhuyan'],
        ['reg_no'=>'221FD01055','student_name'=>'Irfan Sayyad','project_title'=>'Early-Stage Diabetes Risk Prediction Using Ensemble Machine Learning Models','guide_name'=>'Dr. K. Sujatha'],
        ['reg_no'=>'221FD01056','student_name'=>'SK. Nagoor Basha','project_title'=>'Detection of Fake News Using Passive Aggressive Classifier and TF-IDF Vectorization','guide_name'=>'Mr. V. Nagi Reddy'],
        ['reg_no'=>'221FD01057','student_name'=>'Shaik Rahila','project_title'=>'HCC-Predict: A Machine Learning and Deep Learning Approach for Liver Cancer Prediction','guide_name'=>'Dr. G. Shiva Kumar'],
        ['reg_no'=>'221FD01058','student_name'=>'Sk. Shafiya','project_title'=>'Detection of Skin Lesion Using Multiple Residual DenseNet: A Hybrid Approach','guide_name'=>'K. Praveen Kumar'],
        ['reg_no'=>'221FD01059','student_name'=>'Sk. Suhana','project_title'=>'Covid-19 Fake News Detection Using DeBERTa','guide_name'=>'K. Praveen Kumar'],
        ['reg_no'=>'221FD01060','student_name'=>'T. Durga Prasad','project_title'=>"Detecting Alzheimer's Disease Using Deep Learning: Addressing Imbalanced Datasets",'guide_name'=>'Dr. N. Veeranjaneyulu'],
        ['reg_no'=>'221FD01062','student_name'=>'T. Sri Vyshnavi','project_title'=>'Single Image Super Resolution Using ESRGAN','guide_name'=>'Mr. Nyamathulla'],
        ['reg_no'=>'221FD01063','student_name'=>'T. Jagadeesh Kumar','project_title'=>'Enhancing Salt Segmentation in Seismic Images Using U-Net+ and Generative Adversarial Networks','guide_name'=>'Mrs. K. Gayatri'],
        ['reg_no'=>'221FD01064','student_name'=>'T. Bhanu Avinash','project_title'=>'Dual Stream Emotion Recognition Using Facial Expressions','guide_name'=>'V. Nagi Reddy'],
        ['reg_no'=>'221FD01066','student_name'=>'V. Swetha','project_title'=>'Satellite Imagery Change Detection Using Generative Adversarial Network','guide_name'=>'Mr. S. Nyamathulla'],
        ['reg_no'=>'221FD01067','student_name'=>'V. Dharani','project_title'=>"Prediction of Parkinson's Disease Using Classification Methods",'guide_name'=>'Mr. V. Nagireddy'],
        ['reg_no'=>'221FD01068','student_name'=>'Y. Manjula','project_title'=>'Face Recognition for Attendance Purpose Using Deep Learning Techniques','guide_name'=>'Dr. P. Subbarao'],
    ],
];

/* ── Build $batches ── */
$batches = [];
if ($programme === 'mca') {
    foreach ($MCA_BATCH_DATA as $year => $students) {
        $domain_count = []; $guide_count = [];
        foreach ($students as $s) {
            $dom = classifyDomain($s['project_title']);
            $domain_count[$dom] = ($domain_count[$dom] ?? 0) + 1;
            $guide_count[$s['guide_name']] = ($guide_count[$s['guide_name']] ?? 0) + 1;
        }
        arsort($domain_count);
        /* ── FIX: proper label format "2022–2024", not just "2022" ── */
        $batches[intval($year)] = [
            'label'        => intval($year) . '–' . (intval($year) + 2),
            'total'        => count($students),
            'domain_count' => $domain_count,
            'guide_count'  => $guide_count,
        ];
    }
    $res = $conn->query("SELECT reg_no, student_name, project_title, guide_name, year FROM student_submissions WHERE branch='MCA' AND year NOT IN (2022,2023) ORDER BY year");
    if ($res) {
        $db_by_year = [];
        while ($row = $res->fetch_assoc()) $db_by_year[$row['year']][] = $row;
        foreach ($db_by_year as $year => $students) {
            $year = intval($year);
            $domain_count = []; $guide_count = [];
            foreach ($students as $s) {
                $dom = classifyDomain($s['project_title'] ?? '');
                $domain_count[$dom] = ($domain_count[$dom] ?? 0) + 1;
                $guide_count[$s['guide_name'] ?? 'Unknown'] = ($guide_count[$s['guide_name'] ?? 'Unknown'] ?? 0) + 1;
            }
            arsort($domain_count);
            /* ── FIX: same proper label format for DB batches ── */
            $batches[$year] = ['label'=>$year.'–'.($year+2),'total'=>count($students),'domain_count'=>$domain_count,'guide_count'=>$guide_count];
        }
    }
} else {
    $res = $conn->query("SELECT reg_no, student_name, project_title, guide_name, year FROM student_submissions WHERE branch='BCA' ORDER BY year");
    if ($res) {
        $bca_by_year = [];
        while ($row = $res->fetch_assoc()) $bca_by_year[intval($row['year'])][] = $row;
        foreach ($bca_by_year as $year => $students) {
            $domain_count = []; $guide_count = [];
            foreach ($students as $s) {
                $dom = classifyDomain($s['project_title'] ?? '');
                $domain_count[$dom] = ($domain_count[$dom] ?? 0) + 1;
                $guide_count[$s['guide_name'] ?? 'Unknown'] = ($guide_count[$s['guide_name'] ?? 'Unknown'] ?? 0) + 1;
            }
            arsort($domain_count);
            /* ── FIX: BCA is 3-year, label e.g. "2022–2025" ── */
            $batches[$year] = ['label'=>$year.'–'.($year+3),'total'=>count($students),'domain_count'=>$domain_count,'guide_count'=>$guide_count];
        }
    }
}
ksort($batches);

/* ── Batch filter ── */
$selected_batch = $_GET['batch'] ?? 'overall';
if ($selected_batch !== 'overall' && !array_key_exists(intval($selected_batch), $batches)) {
    $selected_batch = 'overall';
}
$filtered_batches = ($selected_batch !== 'overall')
    ? [intval($selected_batch) => $batches[intval($selected_batch)]]
    : $batches;

$all_domain_totals = []; $all_guide_totals = []; $total_students = 0;
foreach ($filtered_batches as $b) {
    $total_students += $b['total'];
    foreach ($b['domain_count'] as $dom => $cnt) $all_domain_totals[$dom] = ($all_domain_totals[$dom] ?? 0) + $cnt;
    foreach ($b['guide_count']  as $g   => $c)   $all_guide_totals[$g]   = ($all_guide_totals[$g]   ?? 0) + $c;
}
arsort($all_domain_totals); arsort($all_guide_totals);

/* ── Navy+Gold palette matching style.css ── */
$domain_colors = [
    'Deep Learning'              => '#1a2744',   /* navy */
    'Machine Learning'           => '#c9952a',   /* gold */
    'Healthcare & Medical AI'    => '#2e5fa3',   /* mid-blue */
    'Computer Vision'            => '#4a7fd4',   /* light blue */
    'NLP & Text'                 => '#243358',   /* navy-light */
    'Cybersecurity & Fraud'      => '#8b6914',   /* dark gold */
    'Data Science & Forecasting' => '#3a6bc4',   /* blue */
    'Speech & Audio'             => '#6b8fcc',   /* soft blue */
    'Full Stack & Systems'       => '#a07820',   /* warm gold */
    'Other / Interdisciplinary'  => '#718096',   /* slate */
];

$batch_years = array_keys($batches);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $prog_label ?> Analytics | CA Project System</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:wght@500;700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

    <style>
        /* ── Page layout ── */
        body { background: var(--bg); }
        .ana-page-wrap {
            max-width: 1200px; margin: 0 auto;
            padding: 28px 28px 60px;
        }

        /* ── Page header ── */
        .ana-page-header {
            display: flex; align-items: center; justify-content: space-between;
            gap: 12px; flex-wrap: wrap; margin-bottom: 22px;
            padding-bottom: 16px; border-bottom: 2px solid var(--border);
        }
        .ana-page-title {
            font-family: 'Fraunces', serif; font-size: 1.6rem;
            font-weight: 700; color: var(--navy); letter-spacing: -0.02em;
        }
        .ana-page-title span {
            display: inline-block; padding: 2px 10px;
            background: var(--navy); color: #fff; border-radius: 6px;
            font-size: 1.1rem; font-weight: 600; vertical-align: middle;
            margin-left: 8px;
        }
        .ana-home-btn {
            padding: 8px 18px; border-radius: 7px; font-size: 0.84rem;
            font-weight: 600; font-family: 'DM Sans', sans-serif;
            background: #fff; color: var(--navy);
            border: 1.5px solid var(--border-strong); text-decoration: none;
            transition: all 0.16s;
        }
        .ana-home-btn:hover { background: var(--navy); color: #fff; border-color: var(--navy); }

        /* ── Tab bar (MCA / BCA) ── */
        .ana-tabs {
            display: flex; gap: 0; margin-bottom: 20px;
            background: #fff; border: 1px solid var(--border);
            border-radius: 9px; padding: 4px; width: fit-content;
            box-shadow: var(--shadow-sm);
        }
        .ana-tab {
            padding: 8px 24px; border-radius: 7px;
            font-family: 'DM Sans', sans-serif; font-size: 0.88rem; font-weight: 600;
            text-decoration: none; color: var(--slate);
            transition: all 0.18s; white-space: nowrap;
        }
        .ana-tab.active {
            background: var(--navy); color: #fff; box-shadow: var(--shadow-sm);
        }
        .ana-tab:hover:not(.active) { background: var(--navy-faint); color: var(--navy); }

        /* ── Batch filter bar ── */
        .batch-filter-wrap {
            display: flex; align-items: center; gap: 8px;
            margin-bottom: 24px; flex-wrap: wrap;
        }
        .batch-filter-label {
            font-size: 0.78rem; font-weight: 600; color: var(--slate-light);
            text-transform: uppercase; letter-spacing: 0.08em; margin-right: 4px;
        }
        .batch-btn {
            padding: 6px 16px; border-radius: 7px;
            font-family: 'DM Sans', sans-serif; font-size: 0.83rem; font-weight: 600;
            text-decoration: none; color: var(--slate);
            background: #fff; border: 1.5px solid var(--border);
            transition: all 0.16s; cursor: pointer;
        }
        .batch-btn:hover { border-color: var(--navy); color: var(--navy); background: var(--navy-faint); }
        .batch-btn.active {
            background: var(--navy); color: #fff; border-color: var(--navy);
        }
        /* Batch span label inside button */
        .batch-btn .batch-span-lbl {
            font-size: 0.72rem; font-weight: 400;
            opacity: 0.75; display: block; line-height: 1;
        }

        /* ── KPI cards ── */
        .kpi-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; margin-bottom: 22px; }
        @media(max-width:700px){ .kpi-row { grid-template-columns: repeat(2,1fr); } }
        .kpi-card {
            background: #fff; border: 1px solid var(--border);
            border-radius: 12px; padding: 18px 20px;
            box-shadow: var(--shadow-sm); position: relative; overflow: hidden;
        }
        .kpi-card::after {
            content:''; position:absolute; top:0; left:0; right:0; height:3px;
            background: var(--navy);
        }
        .kpi-card:nth-child(2)::after { background: var(--gold); }
        .kpi-card:nth-child(3)::after { background: #2e5fa3; }
        .kpi-card:nth-child(4)::after { background: #4a7fd4; }
        .kpi-num {
            font-family: 'Fraunces', serif; font-size: 2rem;
            font-weight: 700; color: var(--navy); line-height: 1;
        }
        .kpi-lbl {
            font-size: 0.71rem; font-weight: 600; text-transform: uppercase;
            letter-spacing: 0.09em; color: var(--slate-light); margin-top: 5px;
        }

        /* ── Section card ── */
        .ana-card {
            background: #fff; border: 1px solid var(--border);
            border-radius: 14px; padding: 22px 24px;
            box-shadow: var(--shadow-sm); margin-bottom: 20px;
        }
        .ana-card-title {
            font-family: 'Fraunces', serif; font-size: 1.05rem;
            font-weight: 700; color: var(--navy); margin-bottom: 4px;
        }
        .ana-card-sub {
            font-size: 0.8rem; color: var(--slate-light); margin-bottom: 18px;
        }

        /* ── Chart grid ── */
        .charts-grid {
            display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;
        }
        @media(max-width:860px){ .charts-grid { grid-template-columns: 1fr; } }
        .charts-grid .full-width { grid-column: 1 / -1; }

        /* ── Domain progress bars ── */
        .dom-list { display: flex; flex-direction: column; gap: 10px; }
        .dom-row { display: flex; flex-direction: column; gap: 4px; }
        .dom-row-top {
            display: flex; justify-content: space-between; align-items: center;
        }
        .dom-name { font-size: 0.84rem; font-weight: 500; color: var(--text-body); }
        .dom-cnt {
            font-size: 0.78rem; font-weight: 600; color: var(--slate-light);
            background: var(--navy-faint); padding: 1px 8px;
            border-radius: 5px; border: 1px solid var(--border);
        }
        .dom-track {
            height: 6px; background: var(--navy-faint);
            border-radius: 4px; overflow: hidden;
        }
        .dom-fill { height: 100%; border-radius: 4px; transition: width .5s ease; }

        /* ── Guide table ── */
        .guide-table {
            width: 100%; border-collapse: separate; border-spacing: 0;
            font-size: 0.87rem; color: var(--text-body);
        }
        .guide-table th {
            text-align: left; padding: 9px 14px;
            border-bottom: 1.5px solid var(--border);
            font-size: 0.72rem; text-transform: uppercase;
            letter-spacing: 0.07em; color: var(--slate-light); font-weight: 600;
            background: var(--navy-faint);
        }
        .guide-table th:first-child { border-radius: 8px 0 0 0; }
        .guide-table th:last-child  { border-radius: 0 8px 0 0; }
        .guide-table td {
            padding: 10px 14px; border-bottom: 1px solid var(--border-light);
            vertical-align: middle;
        }
        .guide-table tr:last-child td { border-bottom: none; }
        .guide-table tr:hover td { background: var(--navy-faint); }
        .guide-pct-bar {
            display: flex; align-items: center; gap: 8px;
        }
        .guide-pct-track {
            flex: 1; height: 5px; background: var(--navy-faint);
            border-radius: 3px; overflow: hidden; max-width: 100px;
        }
        .guide-pct-fill { height: 100%; border-radius: 3px; background: var(--navy); }
        .guide-pct-num { font-size: 0.75rem; color: var(--slate-light); min-width: 28px; }

        /* ── Batch breakdown section ── */
        .batch-breakdown-grid {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 18px;
        }
        .batch-breakdown-card {
            background: var(--navy-faint); border: 1px solid var(--border);
            border-radius: 12px; padding: 18px 16px;
        }
        .bb-header {
            display: flex; align-items: baseline; gap: 10px; margin-bottom: 14px;
            padding-bottom: 10px; border-bottom: 1px solid var(--border);
        }
        .bb-year {
            font-family: 'Fraunces', serif; font-size: 1rem;
            font-weight: 700; color: var(--navy);
        }
        .bb-count {
            font-size: 0.76rem; font-weight: 600; color: var(--slate-light);
            background: #fff; border: 1px solid var(--border);
            padding: 2px 9px; border-radius: 5px;
        }

        /* ── Empty state ── */
        .empty-state {
            text-align: center; padding: 60px 20px;
            color: var(--slate-light); font-size: 0.92rem;
        }
        .empty-state strong { display: block; font-family: 'Fraunces', serif; font-size: 1.1rem; color: var(--navy); margin-bottom: 6px; }

        /* canvas sizing */
        .chart-canvas-wrap { position: relative; }
        .chart-canvas-wrap canvas { max-height: 260px; }
    </style>
</head>
<body>

<div class="ana-page-wrap">

    <!-- ── Page Header ── -->
    <div class="ana-page-header">
        <div>
            <div class="ana-page-title">
                Research Analytics
                <span><?= $prog_label ?></span>
            </div>
            <div style="font-size:.82rem;color:var(--slate-light);margin-top:3px;">
                Department of Computer Applications — VFSTR &nbsp;·&nbsp;
                Project domain distribution across batches
            </div>
        </div>
        <a href="index.php" class="ana-home-btn">← Back to Home</a>
    </div>

    <!-- ── Programme Tabs ── -->
    <div class="ana-tabs">
        <a href="analytics.php?programme=mca&batch=<?= urlencode($selected_batch) ?>"
           class="ana-tab <?= $programme==='mca'?'active':'' ?>">MCA</a>
        <a href="analytics.php?programme=bca&batch=<?= urlencode($selected_batch) ?>"
           class="ana-tab <?= $programme==='bca'?'active':'' ?>">BCA</a>
    </div>

    <!-- ── Batch Filter ── -->
    <div class="batch-filter-wrap">
        <span class="batch-filter-label">Batch</span>
        <a href="?programme=<?= $programme ?>&batch=overall"
           class="batch-btn <?= $selected_batch==='overall'?'active':'' ?>">
            All Batches
        </a>
        <?php foreach ($batch_years as $yr):
            /* ── FIX: get the proper label from $batches array ── */
            $btnLabel = $batches[$yr]['label']; /* e.g. "2023–2025" */
        ?>
            <a href="?programme=<?= $programme ?>&batch=<?= $yr ?>"
               class="batch-btn <?= $selected_batch==$yr?'active':'' ?>">
                <?= htmlspecialchars($btnLabel) ?>
            </a>
        <?php endforeach; ?>
    </div>

    <?php if (empty($filtered_batches)): ?>
        <div class="empty-state">
            <strong>No data available</strong>
            No project data found for <?= $prog_label ?> batch <?= htmlspecialchars($selected_batch) ?>.
            Select another batch or add data first.
        </div>
    <?php else: ?>

        <!-- ── KPI Row ── -->
        <div class="kpi-row">
            <div class="kpi-card">
                <div class="kpi-num"><?= $total_students ?></div>
                <div class="kpi-lbl">Total Students</div>
            </div>
            <div class="kpi-card">
                <div class="kpi-num"><?= count($filtered_batches) ?></div>
                <div class="kpi-lbl">Batches</div>
            </div>
            <div class="kpi-card">
                <div class="kpi-num"><?= count($all_domain_totals) ?></div>
                <div class="kpi-lbl">Research Domains</div>
            </div>
            <div class="kpi-card">
                <div class="kpi-num"><?= count($all_guide_totals) ?></div>
                <div class="kpi-lbl">Project Guides</div>
            </div>
        </div>

        <!-- ── Charts Grid ── -->
        <div class="charts-grid">

            <!-- Doughnut -->
            <div class="ana-card">
                <div class="ana-card-title">Domain Distribution</div>
                <div class="ana-card-sub">Share of each research domain across selected batch(es)</div>
                <div class="chart-canvas-wrap">
                    <canvas id="doughnutChart"></canvas>
                </div>
            </div>

            <!-- Bar comparison -->
            <div class="ana-card">
                <div class="ana-card-title">Batch Comparison</div>
                <div class="ana-card-sub">Top research domains by batch</div>
                <div class="chart-canvas-wrap">
                    <canvas id="batchBarChart"></canvas>
                </div>
            </div>

            <!-- Trend line (only if ≥2 batches) -->
            <?php if (count($filtered_batches) >= 2): ?>
            <div class="ana-card full-width">
                <div class="ana-card-title">Domain Trend Across Batches</div>
                <div class="ana-card-sub">How the top 5 research domains changed over time</div>
                <div class="chart-canvas-wrap">
                    <canvas id="trendLineChart" style="max-height:200px;"></canvas>
                </div>
            </div>
            <?php endif; ?>

            <!-- Guide workload bar -->
            <div class="ana-card <?php echo count($filtered_batches) < 2 ? 'full-width' : ''; ?>">
                <div class="ana-card-title">Guide Workload</div>
                <div class="ana-card-sub">Students assigned per guide (top 8)</div>
                <div class="chart-canvas-wrap">
                    <canvas id="guideChart"></canvas>
                </div>
            </div>

            <!-- Domain breakdown inline bars -->
            <div class="ana-card <?php echo count($filtered_batches) < 2 ? '' : ''; ?>">
                <div class="ana-card-title">Domain Breakdown</div>
                <div class="ana-card-sub">Absolute counts per research domain</div>
                <div class="dom-list">
                    <?php
                    $max_dom = max(array_values($all_domain_totals) ?: [1]);
                    foreach ($all_domain_totals as $dom => $cnt):
                        $clr = $domain_colors[$dom] ?? '#1a2744';
                        $pct = round($cnt / $max_dom * 100);
                    ?>
                    <div class="dom-row">
                        <div class="dom-row-top">
                            <span class="dom-name"><?= htmlspecialchars($dom) ?></span>
                            <span class="dom-cnt"><?= $cnt ?></span>
                        </div>
                        <div class="dom-track">
                            <div class="dom-fill" style="width:<?= $pct ?>%;background:<?= $clr ?>"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

        </div>

        <!-- ── Guide Workload Table ── -->
        <div class="ana-card">
            <div class="ana-card-title">Guide Workload Table</div>
            <div class="ana-card-sub">All guides with student count and percentage of total batch</div>
            <div style="overflow-x:auto;">
                <table class="guide-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Guide Name</th>
                            <th>Students</th>
                            <th>Share</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $gi = 1; foreach ($all_guide_totals as $gn => $gc):
                            $pct = $total_students > 0 ? round($gc / $total_students * 100) : 0;
                        ?>
                        <tr>
                            <td style="color:var(--slate-light);font-size:.8rem;"><?= $gi++ ?></td>
                            <td style="font-weight:500;"><?= htmlspecialchars($gn) ?></td>
                            <td style="font-family:'Fraunces',serif;font-size:1rem;font-weight:700;color:var(--navy);"><?= $gc ?></td>
                            <td>
                                <div class="guide-pct-bar">
                                    <div class="guide-pct-track">
                                        <div class="guide-pct-fill" style="width:<?= min($pct*2,100) ?>%"></div>
                                    </div>
                                    <span class="guide-pct-num"><?= $pct ?>%</span>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ── Per-Batch Domain Breakdown ── -->
        <div class="ana-card">
            <div class="ana-card-title">Per-Batch Domain Breakdown</div>
            <div class="ana-card-sub">Detailed domain distribution for each individual batch</div>
            <div class="batch-breakdown-grid">
                <?php foreach ($filtered_batches as $year => $b):
                    $max_d = max(array_values($b['domain_count']) ?: [1]);
                ?>
                <div class="batch-breakdown-card">
                    <div class="bb-header">
                        <!-- FIX: show full span label e.g. "2023–2025" -->
                        <span class="bb-year"><?= htmlspecialchars($b['label']) ?></span>
                        <span class="bb-count"><?= $b['total'] ?> students</span>
                    </div>
                    <div class="dom-list">
                        <?php foreach ($b['domain_count'] as $dom => $cnt):
                            $clr = $domain_colors[$dom] ?? '#1a2744';
                            $pct = round($cnt / $max_d * 100);
                        ?>
                        <div class="dom-row">
                            <div class="dom-row-top">
                                <span class="dom-name" style="font-size:.79rem;"><?= htmlspecialchars($dom) ?></span>
                                <span class="dom-cnt"><?= $cnt ?></span>
                            </div>
                            <div class="dom-track">
                                <div class="dom-fill" style="width:<?= $pct ?>%;background:<?= $clr ?>"></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

    <?php endif; ?>
</div><!-- /ana-page-wrap -->

<script>
/* ── Chart.js: navy+gold palette matching style.css ── */
const NAVY   = '#1a2744';
const NAVY2  = '#243358';
const NAVY3  = '#2e5fa3';
const NAVY4  = '#4a7fd4';
const GOLD   = '#c9952a';
const GOLD2  = '#e6ab34';
const SLATE  = '#718096';
const BLUE1  = '#3a6bc4';
const BLUE2  = '#6b8fcc';
const MUTED  = '#a0aec0';

/* Global Chart.js defaults — navy/gold theme */
Chart.defaults.font.family     = "'DM Sans', 'Segoe UI', sans-serif";
Chart.defaults.font.size       = 11;
Chart.defaults.color           = '#4a5568';   /* slate */
Chart.defaults.borderColor     = '#e2e8f0';   /* var(--border) */
Chart.defaults.plugins.legend.labels.color = '#4a5568';
Chart.defaults.plugins.legend.labels.boxWidth = 11;
Chart.defaults.plugins.legend.labels.padding  = 12;

const domainColors = <?= json_encode($domain_colors) ?>;
const allDomains   = <?= json_encode($all_domain_totals) ?>;
const batchesRaw   = <?= json_encode(array_map(function($b){
    return ['label'=>$b['label'],'total'=>$b['total'],'domain_count'=>$b['domain_count'],'guide_count'=>$b['guide_count']];
}, $filtered_batches)) ?>;
const guideData    = <?= json_encode($all_guide_totals) ?>;

const domLabels = Object.keys(allDomains);
const domValues = Object.values(allDomains);
const domClrs   = domLabels.map(d => domainColors[d] || NAVY);
const batchKeys = Object.keys(batchesRaw);

/* ── 1. Doughnut ── */
const c1 = document.getElementById('doughnutChart');
if (c1) new Chart(c1, {
    type: 'doughnut',
    data: {
        labels: domLabels,
        datasets: [{
            data: domValues,
            backgroundColor: domClrs,
            borderWidth: 2,
            borderColor: '#f0f3fa',
            hoverBorderColor: '#fff',
        }]
    },
    options: {
        cutout: '62%',
        plugins: {
            legend: { position: 'right', labels: { font: { size: 10 }, padding: 10 } },
            tooltip: {
                callbacks: {
                    label: ctx => ` ${ctx.label}: ${ctx.parsed} students`
                }
            }
        }
    }
});

/* ── 2. Batch Comparison Bar ── */
const c2 = document.getElementById('batchBarChart');
if (c2 && batchKeys.length) {
    const top6 = domLabels.slice(0, 6);
    const batchPalette = [NAVY, GOLD, NAVY3, NAVY4, BLUE1, BLUE2];
    const ds = batchKeys.map((yr, i) => ({
        label: batchesRaw[yr].label,
        data: top6.map(d => batchesRaw[yr].domain_count[d] || 0),
        backgroundColor: batchPalette[i % batchPalette.length],
        borderRadius: 5,
        borderSkipped: false,
    }));
    new Chart(c2, {
        type: 'bar',
        data: { labels: top6, datasets: ds },
        options: {
            plugins: { legend: { position: 'top' } },
            scales: {
                x: { ticks: { font: { size: 9 } }, grid: { color: '#f0f3fa' } },
                y: { beginAtZero: true, ticks: { stepSize: 2 }, grid: { color: '#f0f3fa' } }
            }
        }
    });
}

/* ── 3. Trend Line ── */
const c3 = document.getElementById('trendLineChart');
if (c3 && batchKeys.length >= 2) {
    const top5   = domLabels.slice(0, 5);
    const lClrs  = [NAVY, GOLD, NAVY3, NAVY4, BLUE1];
    const ds = top5.map((dom, i) => ({
        label: dom, tension: 0.35, pointRadius: 5,
        borderColor: lClrs[i],
        backgroundColor: lClrs[i] + '18',
        fill: true,
        data: batchKeys.map(yr => batchesRaw[yr].domain_count[dom] || 0),
    }));
    new Chart(c3, {
        type: 'line',
        data: { labels: batchKeys.map(yr => batchesRaw[yr].label), datasets: ds },
        options: {
            plugins: { legend: { position: 'top' } },
            scales: {
                y: { beginAtZero: true, grid: { color: '#f0f3fa' } },
                x: { grid: { color: '#f0f3fa' } }
            }
        }
    });
}

/* ── 4. Guide Workload (horizontal bar) ── */
const c5 = document.getElementById('guideChart');
if (c5) {
    const gEntries = Object.entries(guideData).slice(0, 8);
    const gNames   = gEntries.map(([n]) => n.length > 22 ? n.slice(0, 20) + '…' : n);
    const gCounts  = gEntries.map(([,v]) => v);
    /* Navy → gold gradient per bar using multiple background colors */
    const gColors  = gCounts.map((_, i) => {
        const t = i / Math.max(gCounts.length - 1, 1);
        /* lerp navy to gold */
        const r = Math.round(0x1a + t * (0xc9 - 0x1a));
        const g = Math.round(0x27 + t * (0x95 - 0x27));
        const b = Math.round(0x44 + t * (0x2a - 0x44));
        return `rgb(${r},${g},${b})`;
    });
    new Chart(c5, {
        type: 'bar',
        data: {
            labels: gNames,
            datasets: [{
                label: 'Students',
                data: gCounts,
                backgroundColor: gColors,
                borderRadius: 5,
                borderSkipped: false,
            }]
        },
        options: {
            indexAxis: 'y',
            plugins: { legend: { display: false } },
            scales: {
                x: { beginAtZero: true, grid: { color: '#f0f3fa' } },
                y: { ticks: { font: { size: 10 } }, grid: { display: false } }
            }
        }
    });
}
</script>
</body>
</html>