
import React, { useState } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Textarea } from '@/components/ui/textarea';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Upload, FileText, CheckCircle, Clock, AlertCircle, Download } from 'lucide-react';
import { toast } from 'sonner';

interface ChecklistItem {
  id: string;
  title: string;
  description: string;
  category: string;
  status: 'not_started' | 'in_progress' | 'satisfactory' | 'not_satisfactory';
  evidence?: File[];
  assessorNotes?: string;
  dateCompleted?: Date;
  assessedBy?: string;
}

interface StudentInterfaceProps {
  checklist: ChecklistItem[];
  onUpdateItem: (itemId: string, updates: Partial<ChecklistItem>) => void;
}

const StudentInterface: React.FC<StudentInterfaceProps> = ({ checklist, onUpdateItem }) => {
  const [selectedFiles, setSelectedFiles] = useState<{ [key: string]: File[] }>({});
  const [notes, setNotes] = useState<{ [key: string]: string }>({});

  const handleFileUpload = (itemId: string, files: FileList | null) => {
    if (!files) return;
    
    const fileArray = Array.from(files);
    setSelectedFiles(prev => ({
      ...prev,
      [itemId]: [...(prev[itemId] || []), ...fileArray]
    }));
    
    // Update item status to in_progress if not already
    const item = checklist.find(i => i.id === itemId);
    if (item && item.status === 'not_started') {
      onUpdateItem(itemId, { status: 'in_progress' });
    }
    
    toast.success(`${fileArray.length} file(s) uploaded for evidence`);
  };

  const removeFile = (itemId: string, fileIndex: number) => {
    setSelectedFiles(prev => ({
      ...prev,
      [itemId]: prev[itemId]?.filter((_, index) => index !== fileIndex) || []
    }));
  };

  const submitEvidence = (itemId: string) => {
    const files = selectedFiles[itemId] || [];
    const note = notes[itemId] || '';
    
    if (files.length === 0 && !note.trim()) {
      toast.error('Please upload evidence or add notes before submitting');
      return;
    }
    
    onUpdateItem(itemId, {
      evidence: files,
      status: 'in_progress'
    });
    
    toast.success('Evidence submitted successfully. Awaiting assessment.');
  };

  const getStatusIcon = (status: string) => {
    switch (status) {
      case 'satisfactory':
        return <CheckCircle className="w-5 h-5 text-green-600" />;
      case 'not_satisfactory':
        return <AlertCircle className="w-5 h-5 text-red-600" />;
      case 'in_progress':
        return <Clock className="w-5 h-5 text-yellow-600" />;
      default:
        return <FileText className="w-5 h-5 text-gray-400" />;
    }
  };

  const getStatusColor = (status: string) => {
    switch (status) {
      case 'satisfactory':
        return 'default';
      case 'not_satisfactory':
        return 'destructive';
      case 'in_progress':
        return 'secondary';
      default:
        return 'outline';
    }
  };

  return (
    <div className="space-y-6">
      <div className="mb-6">
        <h2 className="text-2xl font-bold mb-2">My Assessment Progress</h2>
        <p className="text-gray-600">Upload evidence and track your progress through each assessment item.</p>
      </div>

      {checklist.map(item => (
        <Card key={item.id} className="border-l-4 border-l-blue-500">
          <CardHeader>
            <div className="flex items-start justify-between">
              <div className="flex-1">
                <div className="flex items-center gap-3 mb-2">
                  {getStatusIcon(item.status)}
                  <CardTitle className="text-lg">{item.title}</CardTitle>
                  <Badge variant={getStatusColor(item.status) as any}>
                    {item.status.replace('_', ' ').toUpperCase()}
                  </Badge>
                </div>
                <p className="text-gray-600 mb-2">{item.description}</p>
                <Badge variant="outline" className="text-xs">
                  {item.category}
                </Badge>
              </div>
            </div>
          </CardHeader>

          <CardContent className="space-y-4">
            {/* Evidence Upload Section */}
            <div className="space-y-3">
              <Label className="text-sm font-medium">Upload Evidence</Label>
              
              <div className="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-gray-400 transition-colors">
                <Upload className="w-8 h-8 mx-auto mb-2 text-gray-400" />
                <p className="text-sm text-gray-600 mb-2">
                  Drag and drop files here, or click to select
                </p>
                <Input
                  type="file"
                  multiple
                  onChange={(e) => handleFileUpload(item.id, e.target.files)}
                  className="hidden"
                  id={`file-upload-${item.id}`}
                />
                <Label
                  htmlFor={`file-upload-${item.id}`}
                  className="cursor-pointer"
                >
                  <Button variant="outline" size="sm" asChild>
                    <span>Select Files</span>
                  </Button>
                </Label>
              </div>

              {/* Uploaded Files */}
              {selectedFiles[item.id] && selectedFiles[item.id].length > 0 && (
                <div className="space-y-2">
                  <Label className="text-sm font-medium">Uploaded Files:</Label>
                  {selectedFiles[item.id].map((file, index) => (
                    <div key={index} className="flex items-center justify-between p-2 bg-gray-50 rounded">
                      <div className="flex items-center gap-2">
                        <FileText className="w-4 h-4" />
                        <span className="text-sm">{file.name}</span>
                        <span className="text-xs text-gray-500">
                          ({(file.size / 1024 / 1024).toFixed(2)} MB)
                        </span>
                      </div>
                      <Button
                        variant="ghost"
                        size="sm"
                        onClick={() => removeFile(item.id, index)}
                      >
                        Remove
                      </Button>
                    </div>
                  ))}
                </div>
              )}
            </div>

            {/* Notes Section */}
            <div className="space-y-2">
              <Label htmlFor={`notes-${item.id}`} className="text-sm font-medium">
                Additional Notes (Optional)
              </Label>
              <Textarea
                id={`notes-${item.id}`}
                placeholder="Add any additional context or notes about your evidence..."
                value={notes[item.id] || ''}
                onChange={(e) => setNotes(prev => ({ ...prev, [item.id]: e.target.value }))}
                rows={3}
              />
            </div>

            {/* Assessment Feedback */}
            {item.status === 'satisfactory' || item.status === 'not_satisfactory' ? (
              <div className={`p-4 rounded-lg ${
                item.status === 'satisfactory' ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200'
              }`}>
                <div className="flex items-center gap-2 mb-2">
                  {getStatusIcon(item.status)}
                  <span className="font-medium">
                    Assessment {item.status === 'satisfactory' ? 'Completed' : 'Requires Attention'}
                  </span>
                </div>
                {item.assessorNotes && (
                  <p className="text-sm text-gray-700 mb-2">
                    <strong>Assessor Notes:</strong> {item.assessorNotes}
                  </p>
                )}
                {item.assessedBy && (
                  <p className="text-xs text-gray-600">
                    Assessed by {item.assessedBy} on{' '}
                    {item.dateCompleted?.toLocaleDateString()}
                  </p>
                )}
              </div>
            ) : (
              <div className="flex gap-2">
                <Button
                  onClick={() => submitEvidence(item.id)}
                  disabled={(!selectedFiles[item.id] || selectedFiles[item.id].length === 0) && !notes[item.id]?.trim()}
                  className="flex-1"
                >
                  Submit Evidence
                </Button>
                {item.status === 'in_progress' && (
                  <Badge variant="secondary" className="px-3 py-1">
                    Awaiting Assessment
                  </Badge>
                )}
              </div>
            )}
          </CardContent>
        </Card>
      ))}

      {/* Help Section */}
      <Card className="bg-blue-50 border-blue-200">
        <CardContent className="pt-4">
          <h3 className="font-medium mb-2">Need Help?</h3>
          <ul className="text-sm text-gray-700 space-y-1">
            <li>• Upload photos, videos, documents, or other files as evidence</li>
            <li>• Each item will be marked as Satisfactory or Not Satisfactory by your assessor</li>
            <li>• You can resubmit evidence if an item needs improvement</li>
            <li>• Contact your assessor if you have questions about requirements</li>
          </ul>
        </CardContent>
      </Card>
    </div>
  );
};

export default StudentInterface;
