
import React from 'react';
import MoodleObservationChecklist from '@/components/MoodleObservationChecklist';

const Index: React.FC = () => {
  console.log('Index page loading...');
  
  return (
    <div className="min-h-screen bg-gray-50">
      <div className="container mx-auto py-8">
        <h1 className="text-3xl font-bold text-center mb-8">Moodle Observation Checklist</h1>
        <MoodleObservationChecklist />
      </div>
    </div>
  );
};

export default Index;
