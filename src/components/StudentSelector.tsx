
import React, { useState } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Search, Users, Eye } from 'lucide-react';

interface Student {
  id: string;
  name: string;
  email: string;
  course: string;
  status: 'active' | 'inactive';
  lastActivity?: Date;
}

interface StudentSelectorProps {
  students: Student[];
  selectedStudents: string[];
  onStudentSelect: (studentIds: string[]) => void;
  onStartObservation: (studentIds: string[]) => void;
}

const StudentSelector: React.FC<StudentSelectorProps> = ({
  students,
  selectedStudents,
  onStudentSelect,
  onStartObservation
}) => {
  const [searchTerm, setSearchTerm] = useState('');
  const [filterStatus, setFilterStatus] = useState<'all' | 'active' | 'inactive'>('all');

  const filteredStudents = students.filter(student => {
    const matchesSearch = student.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
                         student.email.toLowerCase().includes(searchTerm.toLowerCase());
    const matchesStatus = filterStatus === 'all' || student.status === filterStatus;
    return matchesSearch && matchesStatus;
  });

  const handleSelectAll = () => {
    if (selectedStudents.length === filteredStudents.length) {
      onStudentSelect([]);
    } else {
      onStudentSelect(filteredStudents.map(s => s.id));
    }
  };

  const handleStudentToggle = (studentId: string) => {
    if (selectedStudents.includes(studentId)) {
      onStudentSelect(selectedStudents.filter(id => id !== studentId));
    } else {
      onStudentSelect([...selectedStudents, studentId]);
    }
  };

  const isAllSelected = selectedStudents.length === filteredStudents.length && filteredStudents.length > 0;
  const isPartiallySelected = selectedStudents.length > 0 && selectedStudents.length < filteredStudents.length;

  return (
    <Card>
      <CardHeader>
        <div className="flex justify-between items-center">
          <CardTitle className="flex items-center gap-2">
            <Users className="w-5 h-5" />
            Select Students for Observation
          </CardTitle>
          <Badge variant="secondary">
            {selectedStudents.length} selected
          </Badge>
        </div>
      </CardHeader>
      <CardContent className="space-y-4">
        {/* Search and Filters */}
        <div className="flex gap-4">
          <div className="relative flex-1">
            <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-4 h-4" />
            <Input
              placeholder="Search students by name or email..."
              value={searchTerm}
              onChange={(e) => setSearchTerm(e.target.value)}
              className="pl-10"
            />
          </div>
          <div className="flex gap-2">
            <Button
              variant={filterStatus === 'all' ? 'default' : 'outline'}
              size="sm"
              onClick={() => setFilterStatus('all')}
            >
              All
            </Button>
            <Button
              variant={filterStatus === 'active' ? 'default' : 'outline'}
              size="sm"
              onClick={() => setFilterStatus('active')}
            >
              Active
            </Button>
            <Button
              variant={filterStatus === 'inactive' ? 'default' : 'outline'}
              size="sm"
              onClick={() => setFilterStatus('inactive')}
            >
              Inactive
            </Button>
          </div>
        </div>

        {/* Select All */}
        <div className="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
          <div className="flex items-center space-x-2">
            <Checkbox
              id="select-all"
              checked={isAllSelected}
              onCheckedChange={handleSelectAll}
              className={isPartiallySelected ? 'data-[state=checked]:bg-blue-600' : ''}
            />
            <label htmlFor="select-all" className="text-sm font-medium">
              Select All ({filteredStudents.length} students)
            </label>
          </div>
          {selectedStudents.length > 0 && (
            <Button 
              onClick={() => onStartObservation(selectedStudents)}
              className="flex items-center gap-2"
            >
              <Eye className="w-4 h-4" />
              Start Observation ({selectedStudents.length})
            </Button>
          )}
        </div>

        {/* Student List */}
        <div className="space-y-2 max-h-96 overflow-y-auto">
          {filteredStudents.map(student => (
            <div 
              key={student.id} 
              className={`flex items-center justify-between p-3 border rounded-lg hover:bg-gray-50 ${
                selectedStudents.includes(student.id) ? 'bg-blue-50 border-blue-200' : ''
              }`}
            >
              <div className="flex items-center space-x-3">
                <Checkbox
                  id={`student-${student.id}`}
                  checked={selectedStudents.includes(student.id)}
                  onCheckedChange={() => handleStudentToggle(student.id)}
                />
                <div className="flex-1">
                  <h4 className="font-medium">{student.name}</h4>
                  <p className="text-sm text-gray-600">{student.email}</p>
                  <p className="text-xs text-gray-500">{student.course}</p>
                </div>
              </div>
              <div className="flex items-center gap-2">
                <Badge 
                  variant={student.status === 'active' ? 'default' : 'secondary'}
                  className="text-xs"
                >
                  {student.status}
                </Badge>
                {student.lastActivity && (
                  <span className="text-xs text-gray-500">
                    Last active: {student.lastActivity.toLocaleDateString()}
                  </span>
                )}
              </div>
            </div>
          ))}
        </div>

        {filteredStudents.length === 0 && (
          <div className="text-center py-8 text-gray-500">
            <Users className="w-12 h-12 mx-auto mb-4 text-gray-300" />
            <p>No students found matching your criteria.</p>
          </div>
        )}
      </CardContent>
    </Card>
  );
};

export default StudentSelector;
