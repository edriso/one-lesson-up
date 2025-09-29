<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class TagSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tags = [
            // Programming & Development
            'Programming', 'Web Development', 'Mobile Development', 'Backend Development', 'Frontend Development',
            'Full Stack', 'JavaScript', 'Python', 'Java', 'C++', 'C#', 'PHP', 'Ruby', 'Go', 'Rust',
            'React', 'Vue.js', 'Angular', 'Node.js', 'Laravel', 'Django', 'Flask', 'Spring Boot',
            'HTML', 'CSS', 'SQL', 'NoSQL', 'MongoDB', 'PostgreSQL', 'MySQL',
            
            // Data Science & Analytics
            'Data Science', 'Machine Learning', 'Artificial Intelligence', 'Deep Learning', 'Data Analysis',
            'Statistics', 'R', 'Pandas', 'NumPy', 'TensorFlow', 'PyTorch', 'Scikit-learn',
            'Data Visualization', 'Tableau', 'Power BI', 'Excel',
            
            // Design & Creative
            'Design', 'UI/UX Design', 'Graphic Design', 'Web Design', 'Logo Design', 'Branding',
            'Adobe Photoshop', 'Adobe Illustrator', 'Figma', 'Sketch', 'Canva',
            'Photography', 'Video Editing', 'Motion Graphics', '3D Modeling', 'Animation',
            
            // Business & Marketing
            'Business', 'Marketing', 'Digital Marketing', 'Social Media Marketing', 'Content Marketing',
            'SEO', 'Google Ads', 'Facebook Ads', 'Email Marketing', 'Analytics',
            'Project Management', 'Agile', 'Scrum', 'Leadership', 'Management',
            'Entrepreneurship', 'Startup', 'E-commerce', 'Sales', 'Customer Service',
            
            // Finance & Accounting
            'Finance', 'Accounting', 'Investing', 'Trading', 'Cryptocurrency', 'Blockchain',
            'Personal Finance', 'Real Estate', 'Tax Planning', 'Financial Planning',
            
            // Health & Fitness
            'Fitness', 'Yoga', 'Meditation', 'Nutrition', 'Weight Loss', 'Muscle Building',
            'Running', 'Cycling', 'Swimming', 'Martial Arts', 'Dance', 'Pilates',
            'Mental Health', 'Stress Management', 'Sleep', 'Wellness',
            
            // Language Learning
            'Language Learning', 'English', 'Spanish', 'French', 'German', 'Italian', 'Portuguese',
            'Chinese', 'Japanese', 'Korean', 'Arabic', 'Russian', 'Hindi',
            'Conversation', 'Grammar', 'Vocabulary', 'Pronunciation',
            
            // Arts
            'Drawing', 'Painting', 'Sculpture', 'Art History', 'Creative Writing',
            
            // Technology & IT
            'IT', 'Cybersecurity', 'Cloud Computing', 'AWS', 'Azure', 'Google Cloud',
            'DevOps', 'Docker', 'Kubernetes', 'Linux', 'System Administration',
            'Networking', 'Cisco', 'CompTIA', 'ITIL', 'Agile', 'Scrum',
            
            // Personal Development
            'Personal Development', 'Productivity', 'Time Management', 'Goal Setting',
            'Communication Skills', 'Public Speaking', 'Leadership', 'Emotional Intelligence',
            'Mindfulness', 'Habits', 'Self-Improvement', 'Career Development',
            
            // Academic & Education
            'Mathematics', 'Physics', 'Chemistry', 'Biology', 'History', 'Geography',
            'Literature', 'Philosophy', 'Psychology', 'Sociology', 'Economics',
            'Academic Writing', 'Research', 'Critical Thinking',
            
            // Hobbies & Crafts
            'Cooking', 'Baking', 'Gardening', 'Woodworking', 'Knitting', 'Crocheting',
            'Pottery', 'Jewelry Making', 'Calligraphy', 'Origami', 'Crafts',
            
            // Professional Skills
            'Microsoft Office', 'Excel', 'PowerPoint', 'Word', 'Google Workspace',
            'Presentation Skills', 'Negotiation', 'Conflict Resolution', 'Team Building',
            'Remote Work', 'Virtual Collaboration', 'Online Teaching',
        ];

        foreach ($tags as $tagName) {
            \App\Models\Tag::firstOrCreate([
                'name' => $tagName,
            ], [
                'is_public' => true,
            ]);
        }
    }
}
