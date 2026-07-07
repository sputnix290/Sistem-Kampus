import React from 'react';

const StudentProfileCard = ({ student }) => {
    return (
        <div className="bg-yellow-100 dark:bg-yellow-900/20 border border-yellow-300 dark:border-yellow-700 rounded-xl p-6 mb-8">
            <h3 className="text-lg font-bold text-gray-800 dark:text-white mb-4">Profil Mahasiswa</h3>
            {student ? (
                <div className="flex items-center gap-6">
                    <div className="w-20 h-20 rounded-full bg-yellow-300 dark:bg-yellow-700 flex items-center justify-center">
                        <span className="text-3xl font-bold text-gray-800 dark:text-white">{student.user.name.split(' ').map(n => n[0]).join('').toUpperCase()}</span>
                    </div>
                    <div>
                        <p className="text-xl font-semibold text-gray-800 dark:text-white">{student.user.name}</p>
                        <p className="text-gray-600 dark:text-yellow-300">{student.nim}</p>
                        <p className="text-gray-500 dark:text-yellow-400">{student.program_studi.name}</p>
                    </div>
                </div>
            ) : (
                <p className="text-gray-600 dark:text-yellow-300">Pilih Mahasiswa untuk melihat profil.</p>
            )}
        </div>
    );
};

export default StudentProfileCard;
