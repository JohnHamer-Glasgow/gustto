alter table teachingtip add whencreated timestamp default 0;
update teachingtip set whencreated = time where whencreated = 0;
alter table teachingtip modify whencreated timestamp default current_timestamp;
alter table teachingtip add school text;

update teachingtip set school = 'Life Sciences' where id = 1;
update teachingtip set school = 'Chemistry' where id = 2;
update teachingtip set school = 'Humanities | Sgoil nan Daonnachdan' where id = 3;
update teachingtip set school = 'Life Sciences' where id = 4;
update teachingtip set school = 'Engineering' where id = 5;
update teachingtip set school = 'Veterinary Medicine' where id = 6;
update teachingtip set school = 'Engineering' where id = 7;
update teachingtip set school = 'Computing Science' where id = 8;
update teachingtip set school = 'Social and Political Sciences' where id = 9;
update teachingtip set school = 'Life Sciences' where id = 10;
update teachingtip set school = 'Humanities | Sgoil nan Daonnachdan' where id = 11;
update teachingtip set school = 'Psychology' where id = 12;
update teachingtip set school = 'Social and Political Sciences' where id = 13;
update teachingtip set school = 'Life Sciences' where id = 14;
update teachingtip set school = 'Physics and Astronomy' where id = 15;
update teachingtip set school = 'Life Sciences' where id = 16;
update teachingtip set school = 'Physics and Astronomy' where id = 17;
update teachingtip set school = 'Physics and Astronomy' where id = 18;
update teachingtip set school = 'Computing Science' where id = 19;
update teachingtip set school = 'Medicine, Dentistry and Nursing' where id = 20;
update teachingtip set school = 'Social and Political Sciences' where id = 21;
update teachingtip set school = 'Social and Political Sciences' where id = 22;
update teachingtip set school = 'Geographical and Earth Sciences' where id = 23;
update teachingtip set school = 'Modern Languages and Cultures' where id = 24;
update teachingtip set school = 'Humanities | Sgoil nan Daonnachdan' where id = 25;
update teachingtip set school = 'Mathematics and Statistics' where id = 26;
update teachingtip set school = 'Computing Science' where id = 27;
update teachingtip set school = 'Computing Science' where id = 28;
update teachingtip set school = 'LEADS' where id = 29;
update teachingtip set school = 'Psychology' where id = 30;
update teachingtip set school = 'Psychology' where id = 32;
update teachingtip set school = 'LEADS' where id = 34;
update teachingtip set school = 'Veterinary Medicine' where id = 35;
update teachingtip set school = 'Life Sciences' where id = 36;
update teachingtip set school = 'Life Sciences' where id = 37;
update teachingtip set school = 'Medicine, Dentistry and Nursing' where id = 39;
update teachingtip set school = 'Chemistry' where id = 40;
update teachingtip set school = 'Chemistry' where id = 41;
update teachingtip set school = 'Chemistry' where id = 42;
update teachingtip set school = 'Life Sciences' where id = 43;
update teachingtip set school = 'Psychology' where id = 44;
update teachingtip set school = 'Critical Studies' where id = 45;
update teachingtip set school = 'Critical Studies' where id = 46;
update teachingtip set school = 'Humanities | Sgoil nan Daonnachdan' where id = 47;
update teachingtip set school = 'Education' where id = 48;
update teachingtip set school = 'Critical Studies' where id = 49;
update teachingtip set school = 'Humanities | Sgoil nan Daonnachdan' where id = 50;
update teachingtip set school = 'Critical Studies' where id = 51;
update teachingtip set school = 'Social and Political Sciences' where id = 52;
update teachingtip set school = 'Social and Political Sciences' where id = 53;
update teachingtip set school = 'Education' where id = 54;
update teachingtip set school = 'Education' where id = 56;
update teachingtip set school = 'Education' where id = 57;
update teachingtip set school = 'Social and Political Sciences' where id = 58;
update teachingtip set school = 'Social and Political Sciences' where id = 59;
update teachingtip set school = 'Other' where id = 62;
update teachingtip set school = 'Computing Science' where id = 63;
update teachingtip set school = 'Humanities | Sgoil nan Daonnachdan' where id = 64;
update teachingtip set school = 'Chemistry' where id = 67;
update teachingtip set school = 'Other' where id = 69;
update teachingtip set school = 'Other' where id = 70;