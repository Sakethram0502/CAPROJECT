<?php
session_start();
include('db.php');
$username = $_SESSION['username'] ?? 'HOD';
$view     = $_GET['view'] ?? 'mca';
$batchYearSelected = isset($_GET['year']) ? (int)$_GET['year'] : 0;
$batchDecadeStart  = isset($_GET['decade']) ? (int)$_GET['decade'] : 0;

/* ═══════════════════════════════════════════════════════
   ALL BATCH DATA — hardcoded from PDF extraction
   231FD = Batch 2023 (enrolled 2023, graduating 2025) — 47 students, Section A
   221FD = Batch 2022 (enrolled 2022, graduating 2024) — 52 students, Section A
   Both batches: every student has a guide name from PDF.
═══════════════════════════════════════════════════════ */
$BATCH_DATA = [

    /* ── 2023 batch (231FD) ── 47 students ── Section A ── */
    2023 => [
        ['reg_no'=>'231FD01001','student_name'=>'A. Sravanthi','section'=>'A','project_title'=>'Novel Brain Tumor Detection Using Deep Learning Techniques','guide_name'=>'Dr. Santhi Sri Kurra'],
        ['reg_no'=>'231FD01002','student_name'=>'A. S. L. Karthik Chaitanya Kumar','section'=>'A','project_title'=>'A Social Media Sentiment Analysis Exploration Using Deep Learning','guide_name'=>'Dr. R. S. Padma Priya'],
        ['reg_no'=>'231FD01004','student_name'=>'A. Bhargav','section'=>'A','project_title'=>'Hybrid CMS-GAN Model for Enhanced Pedestrian Detection Through Unpaired Night-to-Day Translation','guide_name'=>'Dr. Guruswamy Shivakumar'],
        ['reg_no'=>'231FD01005','student_name'=>'A. Harshitha Yamini','section'=>'A','project_title'=>'Emotion Recognition from Speech Using Hybrid Deep Learning Techniques','guide_name'=>'Dr. K. Santhi Sri'],
        ['reg_no'=>'231FD01007','student_name'=>'B. Yaswini Sai','section'=>'A','project_title'=>'Image Captioning Using ResNet-50 and LSTM','guide_name'=>'Dr. Santhi Sri Kurra'],
        ['reg_no'=>'231FD01009','student_name'=>'B. Nandini','section'=>'A','project_title'=>'Food Demand Supply Chain Modelling and Time Series Forecasting Based on ML and DL','guide_name'=>'Dr. Prashanti Guttikonda'],
        ['reg_no'=>'231FD01010','student_name'=>'Ch. Hari Krishna','section'=>'A','project_title'=>'Deep Neural Network Model-Based Ship Images Classification','guide_name'=>'Dr. Siva Koteswara Rao Chinnam'],
        ['reg_no'=>'231FD01012','student_name'=>'Ch. Krishnaveni','section'=>'A','project_title'=>'Healthcare Disease Analysis Using Deep Learning Classification Model','guide_name'=>'Dr. Siva Koteswara Rao Chinnam'],
        ['reg_no'=>'231FD01016','student_name'=>'D. Sai Sasikanth','section'=>'A','project_title'=>'Ad Click Fraud Detection Using Machine Learning and Deep Learning Techniques','guide_name'=>'Dr. Prashanti Guttikonda'],
        ['reg_no'=>'231FD01017','student_name'=>'G. Madhuri','section'=>'A','project_title'=>'Lung Cancer Detection Using Hyperparameter Techniques with Machine Learning Models','guide_name'=>'Dr. Siva Koteswara Rao Chinnam'],
        ['reg_no'=>'231FD01018','student_name'=>'G. N. E. Mounika','section'=>'A','project_title'=>'Brain Tumor Classification and Detection Using Deep Learning','guide_name'=>'Dr. Guruswamy Shivakumar'],
        ['reg_no'=>'231FD01022','student_name'=>'K. Mounika Sai Sri','section'=>'A','project_title'=>'Wind Power Forecasting By Using Machine Learning and Deep Learning','guide_name'=>'Dr. K. Santhi Sri'],
        ['reg_no'=>'231FD01024','student_name'=>'K. Sitamma','section'=>'A','project_title'=>'Dash Sylvereye: A High-Performance Visualization System for City-Scale Street Networks','guide_name'=>'Dr. K. Santhi Sri'],
        ['reg_no'=>'231FD01025','student_name'=>'K. Divya Swetha','section'=>'A','project_title'=>'Adaptive Object Detection with ESRGAN-Enhanced Resolution and Faster R-CNN','guide_name'=>'Dr. Prashanti Guttikonda'],
        ['reg_no'=>'231FD01028','student_name'=>'K. Srinivasasrao','section'=>'A','project_title'=>'Automated Diabetic Retinopathy Grading Using Graph Neural Networks for Enhanced Feature Relationship Analysis in Retinal Images','guide_name'=>'Dr. Gayatri Ketepalli'],
        ['reg_no'=>'231FD01030','student_name'=>'K. B. S. Priyanka','section'=>'A','project_title'=>'Enhancing Emotional Voice Conversion with Intensity Control and Mixed Embedding','guide_name'=>'Mr. Siva Rao Alakunta'],
        ['reg_no'=>'231FD01031','student_name'=>'K. Durga Bhavani','section'=>'A','project_title'=>'Enhanced ADHD Detection in Children with Autism Spectrum Disorder Using Advanced Handwriting Analysis and Machine Learning','guide_name'=>'Dr. Guruswamy Shivakumar'],
        ['reg_no'=>'231FD01032','student_name'=>'K. Pavan Kumar','section'=>'A','project_title'=>'Enhanced Image Translation with Pixel Shuffler and Self-Attention GANs','guide_name'=>'Dr. Guruswamy Shivakumar'],
        ['reg_no'=>'231FD01035','student_name'=>'K. Bala Gopaludu','section'=>'A','project_title'=>'Deep Learning-Driven Multi Genre Music Classification With Ten Sound Types','guide_name'=>'Dr. R. S. Padma Priya'],
        ['reg_no'=>'231FD01036','student_name'=>'M. Rama Krishna Koushik','section'=>'A','project_title'=>'Enhancing Text Classification in EHRs with Adaptive Context-Aware Heterogeneous Graph Attention Networks','guide_name'=>'Dr. Gayatri Ketepalli'],
        ['reg_no'=>'231FD01038','student_name'=>'M. Srimannarayana','section'=>'A','project_title'=>'Evaluating Machine Learning Approaches for Effective Fake News Detection','guide_name'=>'Dr. Gayatri Ketepalli'],
        ['reg_no'=>'231FD01041','student_name'=>'N. Eswar','section'=>'A','project_title'=>'Deep Learning-Based Facial Emotion Recognition: A CNN Approach for Real-Time Applications','guide_name'=>'Dr. Gayatri Ketepalli'],
        ['reg_no'=>'231FD01042','student_name'=>'P. Gopi Krishna','section'=>'A','project_title'=>'A Framework of Segmentation and Classification Models for Breast Tumor from Mammogram Images','guide_name'=>'Mrs. Sk. Nazma Sultana'],
        ['reg_no'=>'231FD01043','student_name'=>'P. Tirumala','section'=>'A','project_title'=>'Voice Authentication at Scale: A Room Level Approach for Smart Devices','guide_name'=>'Mr. D. Anandhakumar'],
        ['reg_no'=>'231FD01044','student_name'=>'P. Azemunnisa','section'=>'A','project_title'=>'Credit Card Fraud Detection Through Time-Aware Behavioral Representation Learning','guide_name'=>'Dr. R. S. Padma Priya'],
        ['reg_no'=>'231FD01045','student_name'=>'P. Srinivasa Reddy','section'=>'A','project_title'=>'Diabetes Prediction Using Machine Learning Techniques','guide_name'=>'Dr. Prashanti Guttikonda'],
        ['reg_no'=>'231FD01046','student_name'=>'P. Teja Sai','section'=>'A','project_title'=>'Gemstone Classification Using Gemtelligence Framework: A Deep Learning Approach','guide_name'=>'Dr. Siva Koteswara Rao Chinnam'],
        ['reg_no'=>'231FD01047','student_name'=>'P. Durga Prasad','section'=>'A','project_title'=>'Enhanced Deep Learning for Diabetic Retinopathy Detection with Optimized Feature Extraction','guide_name'=>'Dr. K. Gayatri'],
        ['reg_no'=>'231FD01049','student_name'=>'Prakash Sharma','section'=>'A','project_title'=>'Deep Learning for Wide Dynamic Range Resolution Reconstruction of Images','guide_name'=>'Dr. Guruswamy Shivakumar'],
        ['reg_no'=>'231FD01051','student_name'=>'R. Vardhan','section'=>'A','project_title'=>'Gastrointestinal Disease Detection Using RegNet, MobileNetV2, EfficientNetB2 and ResNet-152 with Transformers','guide_name'=>'Dr. R. S. Padma Priya'],
        ['reg_no'=>'231FD01052','student_name'=>'R. Mahesh Babu','section'=>'A','project_title'=>'Human Pose Estimation Using AI Tool','guide_name'=>'Dr. K. Santhi Sri'],
        ['reg_no'=>'231FD01053','student_name'=>'S. Sowjanya','section'=>'A','project_title'=>'Graph Neural Networks for Financial Fraud Detection in Online Transactions','guide_name'=>'Dr. Siva Koteswara Rao Chinnam'],
        ['reg_no'=>'231FD01054','student_name'=>'S. Vasavi Venkata Lakshmi','section'=>'A','project_title'=>'Secure Data Access in Cloud Environments Using Quantum Cryptography','guide_name'=>'Dr. Prashanti Guttikonda'],
        ['reg_no'=>'231FD01056','student_name'=>'Shaik Arifunnisa','section'=>'A','project_title'=>'Pre-Trained Models for Breast Cancer Image Classification','guide_name'=>'Dr. K. Santhi Sri'],
        ['reg_no'=>'231FD01058','student_name'=>'Shaik Hafeeza','section'=>'A','project_title'=>'EHR-HGCN: A Transformer Augmented Heterogeneous Graph Convolutional Network for Enhanced Text Classification in Healthcare','guide_name'=>'Dr. K. Gayatri'],
        ['reg_no'=>'231FD01059','student_name'=>'Shaik Mahera','section'=>'A','project_title'=>'Enhancing Breast Cancer Diagnosis Using Modality-Specific Information Disentanglement from MRI','guide_name'=>'Dr. Siva Koteswara Rao Chinnam'],
        ['reg_no'=>'231FD01061','student_name'=>'Soni Kumari','section'=>'A','project_title'=>'A Deep Learning Approach for Underwater Image Enhancement Using CNNs','guide_name'=>'Mrs. R. Naga Sirisha'],
        ['reg_no'=>'231FD01063','student_name'=>'T. Nived Reddy','section'=>'A','project_title'=>'An Enhanced Fake News Detection System with Deep Learning Methods','guide_name'=>'Dr. R. S. Padma Priya'],
        ['reg_no'=>'231FD01064','student_name'=>'T. Chandrashekar','section'=>'A','project_title'=>'Face Detection in Low-Light Conditions Using MTCNN and YOLOv8','guide_name'=>'Mrs. R. Naga Sirisha'],
        ['reg_no'=>'231FD01065','student_name'=>'T. Srihari','section'=>'A','project_title'=>'Instance-Level Human Parts Detection and a New Benchmark','guide_name'=>'Dr. Prashanti Guttikonda'],
        ['reg_no'=>'231FD01067','student_name'=>'T. Syam Tejaswi','section'=>'A','project_title'=>'Context-Aware Chronic Disease Prediction Using Machine Learning','guide_name'=>'Dr. R. S. Padma Priya'],
        ['reg_no'=>'231FD01068','student_name'=>'Tagore Thotakura','section'=>'A','project_title'=>'Lung Cancer Detection Using Machine Learning','guide_name'=>'Mrs. R. Naga Sirisha'],
        ['reg_no'=>'231FD01069','student_name'=>'T. Kamal','section'=>'A','project_title'=>'Disease Prediction and Drug Recommendation Using Machine Learning','guide_name'=>'Dr. Siva Koteswara Rao Chinnam'],
        ['reg_no'=>'231FD01071','student_name'=>'V. Anil','section'=>'A','project_title'=>'Adaptive Context-Aware Framework for Mammography Projection Reconstruction Using Dynamic Deep Learning Architectures','guide_name'=>'Dr. Guruswamy Shivakumar'],
        ['reg_no'=>'231FD01072','student_name'=>'Y. Venkata Arjun Reddy','section'=>'A','project_title'=>'An Optimized Machine Learning Model for Heart Disease Prediction','guide_name'=>'Dr. Siva Koteswara Rao Chinnam'],
        ['reg_no'=>'231FD01074','student_name'=>'L. Venkata Sriram','section'=>'A','project_title'=>'Advanced License Plate Recognition System for Smart Traffic Management and Monitoring','guide_name'=>'Dr. Gayatri Ketepalli'],
        ['reg_no'=>'231FD01075','student_name'=>'A. Chenchi Reddy','section'=>'A','project_title'=>'Dual Stream Emotion Recognition Using Facial Expressions Using Deep Learning','guide_name'=>'Dr. Guruswamy Shivakumar'],
    ],

    /* ── 2022 batch (221FD) ── 52 students ── Section A ── */
    2022 => [
        ['reg_no'=>'221FD01002','student_name'=>'A. Amareshwara Sai Nath','section'=>'A','project_title'=>'Interactive Deep Image Colorization of Quality','guide_name'=>'Dr. Ziaul Haque Choudhury'],
        ['reg_no'=>'221FD01003','student_name'=>'A. Gnaneswari','section'=>'A','project_title'=>'Semantic Segmentation of Brain Tumour Detection Using MRI Images','guide_name'=>'Dr. G. Shiva Kumar'],
        ['reg_no'=>'221FD01009','student_name'=>'B. Naga Prathima','section'=>'A','project_title'=>'Instance-Level Human Parts Detection and a New Benchmark','guide_name'=>'Mrs. K. Gayatri'],
        ['reg_no'=>'221FD01012','student_name'=>'B. Gopinadh','section'=>'A','project_title'=>'A Mobile Application for Real Time Object Detection Using Deep Learning','guide_name'=>'Dr. Hemanta Kumar Bhuyan'],
        ['reg_no'=>'221FD01013','student_name'=>'Boyapati Charani','section'=>'A','project_title'=>'Customized CNN Models for Diabetic Retinopathy Detection: A Comparative Study of Architectural Approaches','guide_name'=>'Dr. Kamepalli Sujatha'],
        ['reg_no'=>'221FD01014','student_name'=>'Ch. Jahnavi','section'=>'A','project_title'=>'Masked Face Recognition: Advancing Accuracy with CNN, Mask Transfer, and Attention Model','guide_name'=>'Dr. Ziaul Haque Choudhury'],
        ['reg_no'=>'221FD01016','student_name'=>'D. Aravind Kumar','section'=>'A','project_title'=>'Identification of Deepfake Images Using Deep Learning','guide_name'=>'Mrs. K. Gayatri'],
        ['reg_no'=>'221FD01018','student_name'=>'Devabattini Naga Divya','section'=>'A','project_title'=>'Exploring the Role of Feature Selection in Developing Robust Cardiovascular Disease Prediction Models','guide_name'=>'Dr. K. Sujatha'],
        ['reg_no'=>'221FD01019','student_name'=>'Devandla Vamsi','section'=>'A','project_title'=>'Real-Time Facial Emotions Recognition Using Deep Learning Approach','guide_name'=>'Dr. P. Subba Rao'],
        ['reg_no'=>'221FD01020','student_name'=>'Dosapati Lakshmi Naga Durga Parvathi','section'=>'A','project_title'=>'A Multi-Model Deep Learning Approach for Lung Cancer Detection','guide_name'=>'Dr. N. Veeranjaneyulu'],
        ['reg_no'=>'221FD01022','student_name'=>'E. Durga Prasad','section'=>'A','project_title'=>'Using Machine Learning for Network Anomaly and Intrusion Detection','guide_name'=>'Mrs. K. Gayatri'],
        ['reg_no'=>'221FD01023','student_name'=>'E. Vasubabu','section'=>'A','project_title'=>'A Multichannel Approach with Graph Centrality Relation to Detect Drowsiness of a Driver','guide_name'=>'K. Praveen Kumar'],
        ['reg_no'=>'221FD01024','student_name'=>'G. Lakshmi Sai Chandrika','section'=>'A','project_title'=>'A Deep Learning Approach to Classifying and Detecting Tomato Leaf Disease','guide_name'=>'Dr. G. Shiva Kumar'],
        ['reg_no'=>'221FD01025','student_name'=>'Gonuguntla Srilatha','section'=>'A','project_title'=>'Crop Recommendation Using Ant Colony Optimization and Semi-Supervised SVM','guide_name'=>'Dr. Siva Koteswararao Chinnam'],
        ['reg_no'=>'221FD01026','student_name'=>'G. N. Koteswararao','section'=>'A','project_title'=>'Deep Learning-Based Semantic Segmentation for Salt Deposit Detection','guide_name'=>'Mrs. K. Gayatri'],
        ['reg_no'=>'221FD01028','student_name'=>'J. Vidyadhari','section'=>'A','project_title'=>'Age Sense Demographic Insight System','guide_name'=>'Dr. K. Santhi Sri'],
        ['reg_no'=>'221FD01029','student_name'=>'J. Vamsi Krishna','section'=>'A','project_title'=>'A Flower Classification Method Combining DenseNet Architecture with SVM','guide_name'=>'Dr. G. Shiva Kumar'],
        ['reg_no'=>'221FD01030','student_name'=>'K. Naga Anjali','section'=>'A','project_title'=>'Cardiovascular Disease Detection Using MultiChannel Inception DenseNet','guide_name'=>'K. Praveen Kumar'],
        ['reg_no'=>'221FD01031','student_name'=>'K. Anusha','section'=>'A','project_title'=>'Medical Image Segmentation Using Enhanced Attention Based Transformer Model','guide_name'=>'Dr. Hemanta Kumar Bhuyan'],
        ['reg_no'=>'221FD01033','student_name'=>'K. Raghava','section'=>'A','project_title'=>'Real-Time Detection of Covid-19 Face Masks with TensorFlow, Keras and OpenCV','guide_name'=>'Dr. P. Subba Rao'],
        ['reg_no'=>'221FD01034','student_name'=>'Kanna Gopi Babu','section'=>'A','project_title'=>'Leveraging Machine Learning to Improve Early Detection of Thyroid Cancer','guide_name'=>'Dr. K. Sujatha'],
        ['reg_no'=>'221FD01035','student_name'=>'K. Gayathri','section'=>'A','project_title'=>'Multi Agent Learning Technique for Feature Selection','guide_name'=>'Dr. Hemanta Kumar Bhuyan'],
        ['reg_no'=>'221FD01036','student_name'=>'K. Usha Rani','section'=>'A','project_title'=>'Deep Learning for Emotion Recognition: Evaluating CNN on Facial Expressions','guide_name'=>'Dr. N. Veeranjaneyulu'],
        ['reg_no'=>'221FD01037','student_name'=>'K. Ramya Sri','section'=>'A','project_title'=>'Zero-Shot Image-to-Image Translation','guide_name'=>'Sk. Nymathulla'],
        ['reg_no'=>'221FD01038','student_name'=>'K. Lakshmi Thanuja','section'=>'A','project_title'=>'Heart Disease Prediction System Using Ensemble Methods','guide_name'=>'Dr. K. Santhi Sri'],
        ['reg_no'=>'221FD01039','student_name'=>'Hima Bindu Kurapati','section'=>'A','project_title'=>'Fraud Detection in Online Payments Using Machine Learning','guide_name'=>'Dr. K. Santhi Sri'],
        ['reg_no'=>'221FD01040','student_name'=>'K. Rajya Lakshmi','section'=>'A','project_title'=>'Diagnosis of Brain Tumor Using Machine Learning Model with Fine Hyper Parameter Tuning Approach','guide_name'=>'V. Nagireddy'],
        ['reg_no'=>'221FD01041','student_name'=>'M. Sumanvitha','section'=>'A','project_title'=>'Deep Learning for Face Recognition Using Multi-Task Cascaded CNN and AlexNet Approaches','guide_name'=>'Dr. P. Subbarao'],
        ['reg_no'=>'221FD01042','student_name'=>'Maram Venkatalakshmi','section'=>'A','project_title'=>'Deep Learning Model for Logo Detection','guide_name'=>'Dr. Siva Koteswararao Chinnam'],
        ['reg_no'=>'221FD01043','student_name'=>'M. Venkata Raju','section'=>'A','project_title'=>'Detection and Recognition of Human Face Emotion Using Neural Network','guide_name'=>'Dr. Ziaul Haque Choudhury'],
        ['reg_no'=>'221FD01044','student_name'=>'M. Sowmya','section'=>'A','project_title'=>'Signet: CNN-Based Technology for Inclusive Communication with Deaf and Mute Individuals','guide_name'=>'Dr. Shiva Kumar G'],
        ['reg_no'=>'221FD01045','student_name'=>'N. Venkata Siva Rao','section'=>'A','project_title'=>'A Probabilistic Linguistic Term Based Product Review Sentiment Analysis Using BERT Representation','guide_name'=>'Mr. K. Praveen Kumar'],
        ['reg_no'=>'221FD01046','student_name'=>'N. Pavan Kalyan','section'=>'A','project_title'=>'Masked Face-Recognition By Using ResNet-50, CV2, SVC Models','guide_name'=>'Dr. P. Subbarao'],
        ['reg_no'=>'221FD01047','student_name'=>'P. Mohan Reddy','section'=>'A','project_title'=>'A Hybrid Stacked Conv Bidirectional LSTM Approach for Predicting Public Opinions from Monkeypox Tweets','guide_name'=>'V. Nagi Reddy'],
        ['reg_no'=>'221FD01048','student_name'=>'P. Alekhya','section'=>'A','project_title'=>'Text and Non-text Segmentation in Printed Document Images Using CNN and VGG16','guide_name'=>'Dr. Shiva Kumar G'],
        ['reg_no'=>'221FD01049','student_name'=>'P. Rahul','section'=>'A','project_title'=>'Face Recognition Using Hybrid Learning Model','guide_name'=>'Dr. Ziaul Haque Choudhury'],
        ['reg_no'=>'221FD01050','student_name'=>'P. Prasanna Lakshmi','section'=>'A','project_title'=>"Alzheimer's Disease Detection Using VGG16",'guide_name'=>'Dr. K. Santhi Sri'],
        ['reg_no'=>'221FD01051','student_name'=>'P. Gowthami','section'=>'A','project_title'=>'Integrated Threat Mitigation in Finger and Palm Vein Biometrics Through Multi-Algorithm Fusion','guide_name'=>'Dr. Ziaul Haque Choudhury'],
        ['reg_no'=>'221FD01052','student_name'=>'P. Ganesh','section'=>'A','project_title'=>'Heart Disease Prediction Using VGG16','guide_name'=>'Dr. Ziaul Haque Choudhury'],
        ['reg_no'=>'221FD01054','student_name'=>'R. Tarun Gopal','section'=>'A','project_title'=>'Adaptive Multilayer Perceptual Attention Network for Facial Expression Recognition','guide_name'=>'Dr. Hemanta Kumar Bhuyan'],
        ['reg_no'=>'221FD01055','student_name'=>'Irfan Sayyad','section'=>'A','project_title'=>'Early-Stage Diabetes Risk Prediction Using Ensemble Machine Learning Models','guide_name'=>'Dr. K. Sujatha'],
        ['reg_no'=>'221FD01056','student_name'=>'SK. Nagoor Basha','section'=>'A','project_title'=>'Detection of Fake News Using Passive Aggressive Classifier and TF-IDF Vectorization','guide_name'=>'Mr. V. Nagi Reddy'],
        ['reg_no'=>'221FD01057','student_name'=>'Shaik Rahila','section'=>'A','project_title'=>'HCC-Predict: A Machine Learning and Deep Learning Approach for Liver Cancer Prediction','guide_name'=>'Dr. G. Shiva Kumar'],
        ['reg_no'=>'221FD01058','student_name'=>'Sk. Shafiya','section'=>'A','project_title'=>'Detection of Skin Lesion Using Multiple Residual DenseNet: A Hybrid Approach','guide_name'=>'K. Praveen Kumar'],
        ['reg_no'=>'221FD01059','student_name'=>'Sk. Suhana','section'=>'A','project_title'=>'Covid-19 Fake News Detection Using DeBERTa','guide_name'=>'K. Praveen Kumar'],
        ['reg_no'=>'221FD01060','student_name'=>'T. Durga Prasad','section'=>'A','project_title'=>"Detecting Alzheimer's Disease Using Deep Learning: Addressing Imbalanced Datasets",'guide_name'=>'Dr. N. Veeranjaneyulu'],
        ['reg_no'=>'221FD01062','student_name'=>'T. Sri Vyshnavi','section'=>'A','project_title'=>'Single Image Super Resolution Using ESRGAN','guide_name'=>'Mr. Nyamathulla'],
        ['reg_no'=>'221FD01063','student_name'=>'T. Jagadeesh Kumar','section'=>'A','project_title'=>'Enhancing Salt Segmentation in Seismic Images Using U-Net+ and Generative Adversarial Networks','guide_name'=>'Mrs. K. Gayatri'],
        ['reg_no'=>'221FD01064','student_name'=>'T. Bhanu Avinash','section'=>'A','project_title'=>'Dual Stream Emotion Recognition Using Facial Expressions','guide_name'=>'V. Nagi Reddy'],
        ['reg_no'=>'221FD01066','student_name'=>'V. Swetha','section'=>'A','project_title'=>'Satellite Imagery Change Detection Using Generative Adversarial Network','guide_name'=>'Mr. S. Nyamathulla'],
        ['reg_no'=>'221FD01067','student_name'=>'V. Dharani','section'=>'A','project_title'=>"Prediction of Parkinson's Disease Using Classification Methods",'guide_name'=>'Mr. V. Nagireddy'],
        ['reg_no'=>'221FD01068','student_name'=>'Y. Manjula','section'=>'A','project_title'=>'Face Recognition for Attendance Purpose Using Deep Learning Techniques','guide_name'=>'Dr. P. Subbarao'],
    ],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HOD Dashboard | Project Management System</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        .year-picker-wrap { max-width: 520px; }
        .year-picker-card {
            background: rgba(255,255,255,0.06);
            border: 1px solid rgba(255,255,255,0.14);
            border-radius: 16px;
            padding: 16px;
        }
        .year-picker-header {
            display: flex; align-items: center;
            justify-content: space-between; margin-bottom: 14px;
        }
        .year-nav-btn {
            border: 1px solid rgba(255,255,255,0.18);
            background: rgba(255,255,255,0.05);
            color: var(--text-dark); border-radius: 10px;
            width: 34px; height: 34px; line-height: 32px;
            text-align: center; text-decoration: none; font-weight: 700;
        }
        .year-picker-title { font-weight:600; color:var(--text-dark); letter-spacing:0.3px; }
        .year-grid { display:grid; grid-template-columns:repeat(4,minmax(80px,1fr)); gap:10px; }
        .year-chip {
            display:block; text-align:center; padding:10px 8px; border-radius:10px;
            text-decoration:none; color:var(--text-dark);
            border:1px solid rgba(255,255,255,0.15);
            background:rgba(255,255,255,0.04); font-weight:500;
        }
        .year-chip.active {
            background:linear-gradient(135deg,rgba(13,74,69,0.92),rgba(11,105,93,0.92));
            border-color:rgba(13,74,69,0.95); color:#fff;
        }
        .year-chip.muted { opacity:0.38; pointer-events:none; }
        .badge-count {
            display:inline-block; font-size:0.72em; font-weight:600;
            background:rgba(13,74,69,0.15); color:var(--green,#0d4a45);
            border-radius:999px; padding:1px 8px; margin-left:6px; vertical-align:middle;
        }
    </style>
</head>
<body>
    <div class="background-overlay"></div>
    <div class="water-animation"></div>

    <div class="dashboard-wrapper">
        <header class="top-nav">
            <div class="top-nav-left">
                <span class="brand-title">Department of Computer Applications</span>
            </div>
            <div class="top-nav-right">
                <span class="welcome-text">Welcome, HOD</span>
                <a href="logout.php" class="btn-link nav-logout">Logout</a>
            </div>
        </header>

        <div class="dashboard-layout">
            <aside class="sidebar">
                <div class="sidebar-title">Menu</div>
                <a href="hod_dashboard.php?view=mca"   class="sidebar-link <?php echo strpos($view,'mca')!==false?'active':''; ?>">MCA</a>
                <a href="hod_dashboard.php?view=bca"   class="sidebar-link <?php echo $view==='bca'  ?'active':''; ?>">BCA</a>
                <a href="hod_dashboard.php?view=staff" class="sidebar-link <?php echo $view==='staff'?'active':''; ?>">Staff</a>
                <a href="hod_dashboard.php?view=batch" class="sidebar-link <?php echo $view==='batch'?'active':''; ?>">Batch</a>
            </aside>
          
            <main class="dashboard-main">

            <?php
            /* MCA — 2 fixed cards only: 1st Year and 2nd Year */
            if ($view === 'mca'):
            ?>
                <h2 class="section-heading">MCA Programme</h2>
                <p class="sub-heading">Select a batch to view student project details</p>
                <div class="card-grid">

                    <!-- MCA 1st Year: 2025-2027 -->
                    <div class="year-card floating">
                        <h3>MCA 1st Year</h3>
                        <p style="font-size:0.78rem;color:#888;margin:2px 0 10px;">Batch 2025-2027</p>
                        <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
                            <a href="hod_dashboard.php?view=mca_detail&batch=2025&section=A" style="display:inline-flex;text-decoration:none;">
                                <span class="pill">Section A</span>
                            </a>
                        </div>
                    </div>

                    <!-- MCA 2nd Year: 2024-2026 -->
                    <div class="year-card floating">
                        <h3>MCA 2nd Year</h3>
                        <p style="font-size:0.78rem;color:#888;margin:2px 0 10px;">Batch 2024-2026</p>
                        <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
                            <a href="hod_dashboard.php?view=mca_detail&batch=2024&section=A" style="display:inline-flex;text-decoration:none;">
                                <span class="pill">Section A</span>
                            </a>
                            <a href="hod_dashboard.php?view=mca_detail&batch=2024&section=B" style="display:inline-flex;text-decoration:none;">
                                <span class="pill">Section B</span>
                            </a>
                        </div>
                    </div>

                </div>

            <?php
            /* ═══════════════════════════════════════════════════════
               MCA SECTION DETAIL
               Pulls hardcoded $BATCH_DATA; merges DB marks if present.
               Guide column always shown — every student has a guide.
            ═══════════════════════════════════════════════════════ */
            elseif ($view === 'mca_detail'):
                $batchStart  = (int)($_GET['batch'] ?? 2023);
                $section     = strtoupper($_GET['section'] ?? 'A');
                $batchSpan   = $batchStart.'-'.($batchStart + 2);
                $currentYear = (int)date('Y');
                // Fixed year labels: 2025=1st, 2024=2nd, 2023=3rd, 2022=4th
                $yearMap  = [2025 => '1st', 2024 => '2nd', 2023 => '3rd', 2022 => '4th'];
                $yearLabel = isset($yearMap[$batchStart]) ? $yearMap[$batchStart].' Year' : 'Alumni';

                if ($batchStart === 2024 && $section === 'B') {
                    // Section B for batch 2024 — load 241FD students from DB
                    $stmt = $conn->prepare("SELECT reg_no, student_name, project_title, guide_name, r1_marks, r2_marks, r3_marks, r4_marks, r5_marks FROM student_submissions WHERE reg_no LIKE '241FD%' ORDER BY reg_no ASC");
                    $stmt->execute();
                    $students = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                } elseif ($batchStart === 2024 && $section === 'A') {
                    // Section A for batch 2024 — no data yet
                    $students = [];
                } elseif (isset($BATCH_DATA[$batchStart])) {
                    $students = $BATCH_DATA[$batchStart];
                } else {
                    // Any other batch not in hardcoded data — no data
                    $students = [];
                }

                // Merge review marks from DB (for hardcoded batches)
                $marksMap = [];
                $mRes = $conn->prepare(
                    "SELECT reg_no, r1_marks, r2_marks, r3_marks, r4_marks, r5_marks FROM student_submissions WHERE branch='MCA'"
                );
                $mRes->execute();
                $mData = $mRes->get_result();
                while ($mr = $mData->fetch_assoc()) $marksMap[$mr['reg_no']] = $mr;
            ?>
                <a href="hod_dashboard.php?view=mca" class="back-link">← Back to MCA Programme</a>
                <h2 class="section-heading">MCA <?php echo $yearLabel; ?> — Section <?php echo htmlspecialchars($section); ?></h2>
                <p class="sub-heading">
                    Batch <?php echo htmlspecialchars($batchSpan); ?>
                    &nbsp;|&nbsp; <?php echo count($students); ?> Students
                
                </p>

                <?php if (empty($students)): ?>
                    <p class="no-data">No data found for batch <?php echo htmlspecialchars($batchSpan); ?>.</p>
                <?php else: ?>
                <div class="table-container">
                    <table class="hod-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Reg No</th>
                                <th>Student Name</th>
                                <th>Project Title</th>
                                <th>Guide</th>
                                <th>R1</th><th>R2</th><th>R3</th><th>R4</th><th>R5</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $i => $row):
                                $m = $marksMap[$row['reg_no']] ?? [];
                            ?>
                            <tr>
                                <td><?php echo $i + 1; ?></td>
                                <td><?php echo htmlspecialchars($row['reg_no']); ?></td>
                                <td><?php echo htmlspecialchars($row['student_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['project_title']); ?></td>
                                <td><?php echo htmlspecialchars($row['guide_name']); ?></td>
                                <td><?php echo (!empty($m['r1_marks'])) ? $m['r1_marks'] : ((!empty($row['r1_marks'])) ? $row['r1_marks'] : '—'); ?></td>
                                <td><?php echo (!empty($m['r2_marks'])) ? $m['r2_marks'] : ((!empty($row['r2_marks'])) ? $row['r2_marks'] : '—'); ?></td>
                                <td><?php echo (!empty($m['r3_marks'])) ? $m['r3_marks'] : ((!empty($row['r3_marks'])) ? $row['r3_marks'] : '—'); ?></td>
                                <td><?php echo (!empty($m['r4_marks'])) ? $m['r4_marks'] : ((!empty($row['r4_marks'])) ? $row['r4_marks'] : '—'); ?></td>
                                <td><?php echo (!empty($m['r5_marks'])) ? $m['r5_marks'] : ((!empty($row['r5_marks'])) ? $row['r5_marks'] : '—'); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>

            <?php
            /* ═══════════════════════════════════════════════════════
               STAFF — cards + drill-down, counts from hardcoded data
            ═══════════════════════════════════════════════════════ */
            elseif ($view === 'staff'):
                $selected_guide = $_GET['guide'] ?? null;
                $all_staff = [
                    'Dr. K. Gayatri',
                    'Dr. K. Santhi Sri',
                    'Dr. M. Srikanth Yadav',
                    'Dr. N. Veeranjaneyulu',
                    'Dr. R. S. Padma Priya',
                    'Dr. Siva Koteswara Rao Chinnam',
                    'Mrs. R. Swathika',
                    'Mrs. R. Naga Sirisha',
                ];
                $staff_profiles = [
                    'Dr. K. Gayatri'                 => ['full_name'=>'Dr Gayatri Ketepalli',        'role'=>'Assistant Professor', 'phone'=>'8555041186', 'email'=>'gk_ca@vignan.ac.in',      'image'=>'staff-gayatri.png'],
                    'Dr. K. Santhi Sri'              => ['full_name'=>'Dr Kurra Santhi Sri',         'role'=>'Professor',           'phone'=>'9297105269', 'email'=>'drkss_ca@vignan.ac.in',   'image'=>'staff-santhi-sri.png'],
                    'Dr. M. Srikanth Yadav'          => ['full_name'=>'Dr Srikanth Yadav M',         'role'=>'Associate Professor', 'phone'=>'8121827423', 'email'=>'sym_it@vignan.ac.in',     'image'=>'staff-srikanth-yadav.png'],
                    'Dr. N. Veeranjaneyulu'          => ['full_name'=>'Dr N. Veeranjaneyulu',        'role'=>'Professor',           'phone'=>'9347162038', 'email'=>'drnvn_it@vignan.ac.in',   'image'=>'staff-veeranjaneyulu.png'],
                    'Dr. R. S. Padma Priya'          => ['full_name'=>'Dr R S Padma Priya',          'role'=>'Associate Professor', 'phone'=>'8056582747', 'email'=>'drpprs_ca@vignan.ac.in',  'image'=>'staff-padma-priya.png'],
                    'Dr. Siva Koteswara Rao Chinnam' => ['full_name'=>'Dr Siva Koteswararao Chinnam','role'=>'Associate Professor', 'phone'=>'9440372374', 'email'=>'drchskr_ca@vignan.ac.in', 'image'=>'staff-siva-koteswararao.png'],
                    'Mrs. R. Swathika'               => ['full_name'=>'Mrs R Swathika',              'role'=>'Assistant Professor', 'phone'=>'9626494680', 'email'=>'rs_ca@vignan.ac.in',       'image'=>'staff-swathika.png'],
                    'Mrs. R. Naga Sirisha'           => ['full_name'=>'Mrs R Naga Sirisha',          'role'=>'Assistant Professor', 'phone'=>'9494852495', 'email'=>'rns_it_tra@vignan.ac.in', 'image'=>'staff-naga-sirisha.png'],
                ];
                // Only 241FD batch 2024 students from DB
                $guideMap = [];
                $dbStmt = $conn->prepare("SELECT reg_no, student_name, project_title, guide_name FROM student_submissions WHERE reg_no LIKE '241FD%' ORDER BY reg_no ASC");
                $dbStmt->execute();
                $dbStudents = $dbStmt->get_result()->fetch_all(MYSQLI_ASSOC);
                foreach ($dbStudents as $s) {
                    $g = trim($s['guide_name'] ?? '');
                    if ($g) $guideMap[$g][] = array_merge($s, ['batch_year' => 2024]);
                }
                function staffGuideRows(array $guideMap, string $staffName): array {
                    $strip  = fn($n) => preg_replace('/^(Dr\.|Mrs\.|Mr\.|Prof\.)\s+/i', '', trim($n));
                    $tokens = fn($n) => array_values(array_filter(preg_split('/[\s.]+/', strtolower($strip($n)))));
                    $staffT = $tokens($staffName);
                    $result = [];
                    foreach ($guideMap as $gName => $rows) {
                        $guideT    = $tokens($gName);
                        $surnameOk = !empty($staffT) && !empty($guideT) && end($staffT) === end($guideT);
                        $common    = count(array_intersect($staffT, $guideT));
                        if ($surnameOk && $common >= 2) {
                            foreach ($rows as $r) $result[] = $r;
                        }
                    }
                    usort($result, fn($a, $b) => strcmp($a['reg_no'], $b['reg_no']));
                    return $result;
                }
            ?>
                <h2 class="section-heading">Staff — Project Guides</h2>
                <p class="sub-heading">Click a card to view assigned students</p>

                <div class="staff-card-grid">
                    <?php foreach ($all_staff as $name):
                        $cnt = count(staffGuideRows($guideMap, $name));
                    ?>
                        <div class="staff-card <?php echo ($selected_guide === $name)?'active':''; ?>"
                             onclick="location.href='hod_dashboard.php?view=staff&guide=<?php echo urlencode($name); ?>'">
                            <h4><?php echo htmlspecialchars($name); ?></h4>
                            <div class="count"><?php echo $cnt; ?></div>
                            <div class="label">students assigned</div>
                            <?php if (isset($staff_profiles[$name])): $p = $staff_profiles[$name]; ?>
                                <div class="staff-profile">
                                    <img src="<?php echo htmlspecialchars($p['image']); ?>" alt="<?php echo htmlspecialchars($p['full_name']); ?>">
                                    <div class="profile-title">Profile</div>
                                    <p class="profile-name"><?php echo htmlspecialchars($p['full_name']); ?></p>
                                    <p class="profile-role"><?php echo htmlspecialchars($p['role']); ?></p>
                                    <p class="profile-meta"><strong>PH:</strong> <?php echo htmlspecialchars($p['phone']); ?></p>
                                    <p class="profile-meta"><strong>Email:</strong> <?php echo htmlspecialchars($p['email']); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php if ($selected_guide):
                    $allocated = staffGuideRows($guideMap, $selected_guide);
                ?>
                    <h3 style="color:var(--text-dark);margin-top:36px;font-family:'Playfair Display',serif;font-size:1.1rem;font-weight:600;">
                        Students under <?php echo htmlspecialchars($selected_guide); ?>
                        <span class="badge-count"><?php echo count($allocated); ?></span>
                    </h3>
                    <?php if (empty($allocated)): ?>
                        <p class="no-data">No students allocated to this guide yet.</p>
                    <?php else: ?>
                    <div class="table-container">
                        <table class="hod-table">
                            <thead>
                                <tr><th>#</th><th>Reg No</th><th>Student Name</th><th>Batch</th><th>Project Title</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($allocated as $i => $r): ?>
                                <tr>
                                    <td><?php echo $i + 1; ?></td>
                                    <td><?php echo htmlspecialchars($r['reg_no']); ?></td>
                                    <td><?php echo htmlspecialchars($r['student_name']); ?></td>
                                    <td><?php echo htmlspecialchars((string)$r['batch_year']); ?></td>
                                    <td><?php echo htmlspecialchars($r['project_title']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                <?php endif; ?>

            <?php
            elseif ($view === 'bca_detail'):
                $batch     = $_GET['batch'] ?? 'bca1';
                $section   = strtoupper($_GET['section'] ?? 'A');
                $yearMap   = ['bca1' => '1st', 'bca2' => '2nd', 'bca3' => '3rd'];
                $yearLabel = isset($yearMap[$batch]) ? $yearMap[$batch].' Year' : $batch;
            ?>
                <a href="hod_dashboard.php?view=bca" class="back-link">← Back to BCA Programme</a>
                <h2 class="section-heading">BCA <?php echo $yearLabel; ?> — Section <?php echo htmlspecialchars($section); ?></h2>
                <p class="no-data">No data available for this section yet.</p>
            <?php
            /* ═══════════════════════════════════════════════════════
               BCA — programme cards
            ═══════════════════════════════════════════════════════ */
            elseif ($view === 'bca'):
            ?>
                <h2 class="section-heading">BCA Programme</h2>
                <p class="sub-heading">Student data will appear here once imported.</p>
                <div class="card-grid">
                    <div class="year-card floating"><h3>BCA 1st Year</h3><div class="pill-row" style="display:flex;gap:8px;"><a href="hod_dashboard.php?view=bca_detail&batch=bca1&section=A" style="text-decoration:none;"><span class="pill">Section A</span></a></div></div>
                    <div class="year-card floating"><h3>BCA 2nd Year</h3><div class="pill-row" style="display:flex;gap:8px;"><a href="hod_dashboard.php?view=bca_detail&batch=bca2&section=A" style="text-decoration:none;"><span class="pill">Section A</span></a></div></div>
                    <div class="year-card floating"><h3>BCA 3rd Year</h3><div class="pill-row" style="display:flex;gap:8px;"><a href="hod_dashboard.php?view=bca_detail&batch=bca3&section=A" style="text-decoration:none;"><span class="pill">Section A</span></a></div></div>
                </div>

            <?php
            /* ═══════════════════════════════════════════════════════
               BATCH — year picker showing hardcoded batch data
            ═══════════════════════════════════════════════════════ */
            elseif ($view === 'batch'):
                $batchCourse  = strtolower($_GET['course'] ?? 'mca');
                $startYear    = 2022;  // Batches start from 2022
                $currentYear  = (int)date('Y');
                $selectedYear = ($batchYearSelected >= $startYear && $batchYearSelected <= $currentYear)
                                ? $batchYearSelected : 0;
                $defaultDecade = (int)(floor(($selectedYear ?: $currentYear) / 10) * 10);
                $decadeStart   = $batchDecadeStart > 0 ? $batchDecadeStart : $defaultDecade;
                $decadeStart   = max($decadeStart, $startYear);
                $prevDecade    = $decadeStart - 10;
                $nextDecade    = $decadeStart + 10;
            ?>
                <h2 class="section-heading">Batch</h2>
                <p class="sub-heading">Select a programme and batch year</p>

                <div style="display:flex;gap:10px;margin-bottom:20px;">
                    <a href="hod_dashboard.php?view=batch&course=mca"
                       style="padding:8px 22px;border-radius:8px;text-decoration:none;font-weight:600;font-size:0.88rem;
                              <?php echo $batchCourse==='mca'?'background:var(--navy,#1a2744);color:#fff;':'background:#f0f0f0;color:#444;'; ?>">MCA</a>
                    <a href="hod_dashboard.php?view=batch&course=bca"
                       style="padding:8px 22px;border-radius:8px;text-decoration:none;font-weight:600;font-size:0.88rem;
                              <?php echo $batchCourse==='bca'?'background:var(--navy,#1a2744);color:#fff;':'background:#f0f0f0;color:#444;'; ?>">BCA</a>
                </div>

                <div class="year-picker-wrap">
                    <div class="year-picker-card">
                        <div class="year-picker-header">
                            <a class="year-nav-btn" href="hod_dashboard.php?view=batch&course=<?php echo $batchCourse; ?>&decade=<?php echo $prevDecade; ?>">&#8249;</a>
                            <div class="year-picker-title"><?php echo $startYear; ?> – 2030</div>
                            <a class="year-nav-btn" href="hod_dashboard.php?view=batch&course=<?php echo $batchCourse; ?>&decade=<?php echo $nextDecade; ?>">&#8250;</a>
                        </div>
                        <div class="year-grid">
                            <?php for ($yr = $startYear; $yr <= 2030; $yr++): ?>
                                <a href="hod_dashboard.php?view=batch&course=<?php echo $batchCourse; ?>&year=<?php echo $yr; ?>"
                                   class="year-chip <?php echo ($selectedYear===$yr)?'active':''; ?>">
                                    <?php echo $yr; ?>
                                </a>
                            <?php endfor; ?>
                        </div>
                    </div>
                </div>

                <?php if ($selectedYear && $batchCourse === 'mca'):
                    if ($selectedYear === 2024) {
                        // Batch 2024: load Section B (241FD) students from DB
                        $stmt241 = $conn->prepare(
                            "SELECT reg_no, student_name, project_title, guide_name FROM student_submissions WHERE reg_no LIKE '241FD%' ORDER BY reg_no ASC"
                        );
                        $stmt241->execute();
                        $batchRows = $stmt241->get_result()->fetch_all(MYSQLI_ASSOC);
                    } else {
                        $batchRows = $BATCH_DATA[$selectedYear] ?? [];
                    }
                    if (empty($batchRows)): ?>
                        <p class="no-data" style="margin-top:20px;">No data available for MCA batch <?php echo $selectedYear; ?>.</p>
                    <?php else: ?>
                        <h3 style="color:var(--text-dark);margin-top:32px;font-family:'Playfair Display',serif;font-size:1.1rem;font-weight:600;">
                            MCA — Batch <?php echo $selectedYear; ?>
                            <?php echo ($selectedYear === 2024) ? '' : ''; ?>
                            <span class="badge-count"><?php echo count($batchRows); ?> students</span>
                        </h3>
                        <div class="table-container">
                            <table class="hod-table">
                                <thead>
                                    <tr>
                                        <th>#</th><th>Reg No</th><th>Student Name</th>
                                        <th>Project Title</th><th>Guide</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($batchRows as $i => $row): ?>
                                    <tr>
                                        <td><?php echo $i+1; ?></td>
                                        <td><?php echo htmlspecialchars($row['reg_no']); ?></td>
                                        <td><?php echo htmlspecialchars($row['student_name']); ?></td>
                                        <td><?php echo htmlspecialchars($row['project_title']); ?></td>
                                        <td><?php echo htmlspecialchars($row['guide_name']); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif;
                endif; ?>

            <?php endif; ?>

            </main>
        </div>
    </div>
</body>
</html>